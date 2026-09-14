<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Jobs\UpdateStockQuotesJob;
use App\Jobs\FetchStockHistoryJob;
use App\Jobs\ProcessStockNewsJob;
use App\Jobs\ProcessAutomaticNavUpdatesJob;
use App\Services\StockPriceMonitorService;
use App\Services\InvestmentPriceMonitorService;

class CronQueueManager extends Command
{
    protected $signature = 'cron:queue-manager {action=monitor : Action to perform (monitor|start|dispatch|cleanup|investments|stock-monitor|investment-monitor|news|run-all)}';
    protected $description = 'Manage queue for shared hosting via cron';

    /** Correlates every log line for a single invocation */
    private string $runId;

    public function handle()
    {
        $this->runId = (string) Str::uuid();
        $action = $this->argument('action');

        $this->log('info', 'CronQueueManager invoked', [
            'action'   => $action,
            'php_sapi' => php_sapi_name(),
            'env'      => app()->environment(),
            'time'     => now()->toIso8601String(),
        ]);

        switch ($action) {
            case 'monitor':
                $this->monitorQueue();
                break;
            case 'run-all':
                $this->runAllTasks();
                break;
            case 'start':
                $this->startQueue();
                break;
            case 'dispatch':
                $this->dispatchStockUpdates();
                break;
            case 'cleanup':
                $this->runCleanup();
                break;
            case 'investments':
                $this->executeAutomaticInvestments();
                break;
            case 'stock-monitor':
                $this->monitorStockPrices();
                break;
            case 'investment-monitor':
                $this->monitorInvestmentPrices();
                break;
            case 'news':
                $this->processStockNews();
                break;
            case 'automatic-nav':
                $this->processAutomaticNavUpdates();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->log('warning', 'Unknown action', ['action' => $action]);
                return 1;
        }

        $this->log('info', 'CronQueueManager finished', ['action' => $action]);
        return 0;
    }

    private function monitorQueue()
    {
        $this->info("Starting comprehensive system monitoring with caching...");
        $this->log('info', 'monitorQueue started');

        // With sync queues, jobs run immediately - no need to check queue status
        $isRunning = $this->isQueueWorkerActive();
        $this->log('info', 'Queue worker check', ['is_running' => $isRunning]);

        if (!$isRunning) {
            $this->log('warning', 'Queue worker not running; starting');
            $this->startQueue();
        }

        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        $this->log('info', 'Failed jobs count', ['failed_jobs' => $failedJobs]);
        if ($failedJobs > 0) {
            $this->retryFailedJobs();
        }

        // Run monitoring tasks with caching
        $this->runCachedTask('automatic_investments', 60, function() {
            $this->monitorAutomaticInvestments();
        });

        $this->runCachedTask('stock_prices', 30, function() {
            $this->monitorStockPrices();
        });

        $this->runCachedTask('investment_prices', 30, function() {
            $this->monitorInvestmentPrices();
        });

        $this->runCachedTask('stock_news', 360, function() {
            $this->processStockNews();
        });

        $this->log('info', 'monitorQueue completed');
        $this->info("System monitoring completed with caching");
    }

    private function startQueue()
    {
        // With sync queues, jobs run immediately
        $this->info("Sync queue mode - jobs run immediately");
        $this->log('info', 'startQueue called (sync mode)');
    }

    private function retryFailedJobs()
    {
        // With sync queues, no failed jobs to retry
        $this->info("Sync queue mode - no failed jobs to retry");
        $this->log('info', 'retryFailedJobs skipped (sync mode)');
    }

    private function dispatchStockUpdates()
    {
        $this->info("Running stock updates synchronously");
        $this->log('info', 'dispatchStockUpdates started');

        $stocks = DB::table('stocks')
            ->where('is_active', true)
            ->get(['id', 'symbol']);

        $this->log('info', 'Active stocks fetched', ['count' => $stocks->count()]);

        if ($stocks->isEmpty()) {
            $this->info("No active stocks found");
            $this->log('info', 'No active stocks found');
            return;
        }

        foreach ($stocks as $index => $stock) {
            try {
                $start = microtime(true);
                $job = new UpdateStockQuotesJob();
                // Resolve dependencies through container
                app()->call([$job, 'handle']);
                $durationMs = (int) round((microtime(true) - $start) * 1000);

                $this->info("Updated stock: {$stock->symbol}");
                $this->log('info', 'Stock updated', [
                    'symbol'      => $stock->symbol,
                    'duration_ms' => $durationMs,
                    'index'       => $index + 1,
                    'total'       => $stocks->count(),
                ]);

                // Respect rate limits with a small delay
                if ($index < $stocks->count() - 1) {
                    sleep(2);
                }
            } catch (\Exception $e) {
                $this->error("Error updating stock {$stock->symbol}: " . $e->getMessage());
                $this->log('error', 'Stock update error', [
                    'symbol' => $stock->symbol,
                    'error'  => $e->getMessage(),
                ]);

                if ($index < $stocks->count() - 1) {
                    sleep(2);
                }
            }
        }

        $this->log('info', 'dispatchStockUpdates completed');
        $this->info("Stock updates completed");
    }

    private function runCleanup()
    {
        $this->info("Running cleanup synchronously");
        $this->log('info', 'runCleanup started');

        try {
            $job = new \App\Jobs\CleanupOldDataJob();
            $start = microtime(true);
            $job->handle();
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->info("Cleanup completed successfully");
            $this->log('info', 'runCleanup completed', ['duration_ms' => $durationMs]);
        } catch (\Exception $e) {
            $this->error("Error during cleanup: " . $e->getMessage());
            $this->log('error', 'runCleanup error', ['error' => $e->getMessage()]);
        }
    }

    private function executeAutomaticInvestments()
    {
        $this->log('info', 'executeAutomaticInvestments started');
        $exitCode = \Artisan::call('investments:execute-automatic');
        $this->log('info', 'executeAutomaticInvestments finished', ['exit_code' => $exitCode]);

        if ($exitCode !== 0) {
            $this->error("Automatic investments execution failed");
        }
    }

    private function monitorAutomaticInvestments()
    {
        $totalPlans = DB::table('automatic_investment_plans')->count();
        $activePlans = DB::table('automatic_investment_plans')->where('is_active', true)->count();
        $duePlans = DB::table('automatic_investment_plans')
            ->where('is_active', true)
            ->where('next_investment_date', '<=', now())
            ->count();

        $this->log('info', 'Automatic investments status', [
            'total'  => $totalPlans,
            'active' => $activePlans,
            'due'    => $duePlans,
        ]);

        if ($duePlans > 0) {
            $this->executeAutomaticInvestments();
        }
    }

    private function monitorStockPrices()
    {
        $this->log('info', 'monitorStockPrices started');

        try {
            $start = microtime(true);
            $result = StockPriceMonitorService::runMonitoring();
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->log('info', 'monitorStockPrices completed', [
                'duration_ms' => $durationMs,
                'result'      => $result ?? null,
            ]);
        } catch (\Exception $e) {
            $this->error("Error during stock monitoring: " . $e->getMessage());
            $this->log('error', 'monitorStockPrices error', ['error' => $e->getMessage()]);
        }
    }

    private function monitorInvestmentPrices()
    {
        $this->log('info', 'monitorInvestmentPrices started');

        try {
            $start = microtime(true);
            $result = InvestmentPriceMonitorService::runMonitoring();
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->log('info', 'monitorInvestmentPrices completed', [
                'duration_ms' => $durationMs,
                'result'      => $result ?? null,
            ]);
        } catch (\Exception $e) {
            $this->error("Error during investment monitoring: " . $e->getMessage());
            $this->log('error', 'monitorInvestmentPrices error', ['error' => $e->getMessage()]);
        }
    }

    private function processStockNews()
    {
        $this->info("Processing stock news synchronously");
        $this->log('info', 'processStockNews started');

        try {
            // At most once every 6 hours
            $lastRunTs = $this->readLastRun('cron:last_stock_news_run');
            $now = now();
            $nowTs = $now->timestamp;
            $recent = $lastRunTs && ($nowTs - $lastRunTs) < (6 * 3600);

            $this->log('info', 'processStockNews gate', [
                'last_run_ts' => $lastRunTs,
                'recent'      => $recent,
            ]);

            if ($recent) {
                $this->info("Stock news processing skipped - too recent");
                $this->log('info', 'processStockNews skipped (recent)');
                return;
            }

            $start = microtime(true);
            $job = new ProcessStockNewsJob();
            app()->call([$job, 'handle']);
            Cache::put('cron:last_stock_news_run', $nowTs, 60 * 60 * 24); // remember for up to 24h
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $this->info("Stock news processing completed");
            $this->log('info', 'processStockNews completed', [
                'duration_ms'  => $durationMs,
                'cached_until' => $now->clone()->addHours(24)->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            $this->error("Error while processing stock news: " . $e->getMessage());
            $this->log('error', 'processStockNews error', ['error' => $e->getMessage()]);
        }
    }

    private function isQueueWorkerActive()
    {
        // With sync queues, jobs run immediately - no need to check queue status
        return true;
    }

    /**
     * Determine if a task should run based on last run time and cooldown.
     *
     * Uses readLastRun() to normalize the cached value and ensures consistent UTC handling
     * (app timezone is UTC). Logs a small trace for observability.
     */
    private function shouldRunTask(string $cacheKey, int $cooldownMinutes): bool
    {
        $lastRunTs = $this->readLastRun($cacheKey);
        $nowTs = now()->timestamp;

        $this->log('info', 'shouldRunTask check', [
            'cache_key'         => $cacheKey,
            'last_run_ts'       => $lastRunTs,
            'cooldown_minutes'  => $cooldownMinutes,
        ]);

        if ($lastRunTs === null) {
            // Never run before
            return true;
        }

        $elapsedMin = max(0, intdiv($nowTs - $lastRunTs, 60));
        if ($elapsedMin < $cooldownMinutes) {
            $this->log('info', 'shouldRunTask false', [
                'elapsed_min'          => $elapsedMin,
                'next_run_in_minutes'  => $cooldownMinutes - $elapsedMin,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Process automatic NAV updates
     */
    private function processAutomaticNavUpdates()
    {
        $cacheKey = 'cron_automatic_nav_updates_last_run';
        $cooldownMinutes = 5; // Run every 5 minutes to check for due updates

        if (!$this->shouldRunTask($cacheKey, $cooldownMinutes)) {
            $this->info("⏭️  Automatic NAV updates skipped (recently executed)");
            return;
        }

        $this->info("🔄 Processing automatic NAV updates...");
        $this->log('info', 'processAutomaticNavUpdates started');

        try {
            $startTime = microtime(true);

            // Dispatch the automatic NAV updates job
            ProcessAutomaticNavUpdatesJob::dispatch();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->info("✅ Automatic NAV updates job dispatched successfully ({$duration}ms)");
            $this->log('info', 'processAutomaticNavUpdates completed', [
                'duration_ms' => $duration,
            ]);

            // Update cache
            Cache::put($cacheKey, now(), now()->addMinutes($cooldownMinutes));

        } catch (\Exception $e) {
            $this->error("❌ Automatic NAV updates failed: " . $e->getMessage());
            $this->log('error', 'processAutomaticNavUpdates failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Run a task only if it hasn't been run recently (based on cache).
     *
     * @param string   $taskName The name of the task for caching
     * @param int      $minutes  Minimum minutes between executions
     * @param callable $task     The task to execute
     */
    private function runCachedTask($taskName, $minutes, callable $task)
    {
        $cacheKey = "cron:last_{$taskName}_run";
        $lastRunTs = $this->readLastRun($cacheKey);
        $nowTs = now()->timestamp;

        $this->log('info', 'runCachedTask check', [
            'task'             => $taskName,
            'cache_key'        => $cacheKey,
            'last_run_ts'      => $lastRunTs,
            'interval_minutes' => $minutes,
        ]);

        $elapsedMin = $lastRunTs ? max(0, intdiv($nowTs - $lastRunTs, 60)) : null;

        if ($elapsedMin !== null && $elapsedMin < $minutes) {
            $nextIn = $minutes - $elapsedMin;
            $this->info("⏭️ Skipping {$taskName} - last run {$elapsedMin}min ago (next in {$nextIn}min)");
            $this->log('info', 'runCachedTask skipped', [
                'task'                 => $taskName,
                'elapsed_min'          => $elapsedMin,
                'next_run_in_minutes'  => $nextIn,
            ]);
            return false;
        }

        $this->info("🔄 Running {$taskName}...");
        $this->log('info', 'runCachedTask started', ['task' => $taskName]);
        $startTime = microtime(true);

        try {
            $task();

            $duration = (int) round((microtime(true) - $startTime) * 1000);
            // Store last run time with longer TTL to prevent cache expiration issues
            Cache::put($cacheKey, $nowTs, 60 * 60 * 48); // 48 hours TTL, store as unix timestamp

            $this->info("✅ {$taskName} completed successfully in {$duration}ms");
            $this->log('info', 'runCachedTask completed', [
                'task'          => $taskName,
                'duration_ms'   => $duration,
                'cached_at_ts'  => $nowTs,
            ]);
            return true;
        } catch (\Exception $e) {
            $duration = (int) round((microtime(true) - $startTime) * 1000);
            $this->error("❌ {$taskName} failed after {$duration}ms: " . $e->getMessage());
            $this->log('error', 'runCachedTask failed', [
                'task'        => $taskName,
                'duration_ms' => $duration,
                'error'       => $e->getMessage(),
            ]);
            // Don't update cache on failure to allow retry sooner
            return false;
        }
    }

    /**
     * Run all tasks with intelligent caching.
     */
    private function runAllTasks()
    {
        $this->info("🚀 Running all system tasks with intelligent caching...");
        $this->log('info', 'runAllTasks started');

        // Show cache status first
        $this->showCacheStatus();

        $tasksRun = 0;
        $tasksSkipped = 0;

        // Stock updates + monitors (highest priority)
        if ($this->runCachedTask('stock_and_prices', 15, function() {
            $this->dispatchStockUpdates();
            $this->monitorStockPrices();
            $this->monitorInvestmentPrices();
        })) { $tasksRun++; } else { $tasksSkipped++; }

        // Automatic investments
        if ($this->runCachedTask('automatic_investments', 60, function() {
            $this->monitorAutomaticInvestments();
        })) { $tasksRun++; } else { $tasksSkipped++; }

        // Automatic NAV updates (check every 5 minutes)
        if ($this->runCachedTask('automatic_nav_updates', 5, function() {
            $this->processAutomaticNavUpdates();
        })) { $tasksRun++; } else { $tasksSkipped++; }

        // Stock news processing (less frequent)
        if ($this->runCachedTask('stock_news', 360, function() {
            $this->processStockNews();
        })) { $tasksRun++; } else { $tasksSkipped++; }

        // Cleanup (least frequent)
        if ($this->runCachedTask('cleanup', 1440, function() {
            $this->runCleanup();
        })) { $tasksRun++; } else { $tasksSkipped++; }

        $this->log('info', 'runAllTasks finished', [
            'ran'     => $tasksRun,
            'skipped' => $tasksSkipped,
        ]);
        $this->info("🎯 All tasks completed! Ran: {$tasksRun}, Skipped: {$tasksSkipped}");
    }

    /**
     * Show current cache status for all tasks.
     */
    private function showCacheStatus()
    {
        $this->info("📊 Cache Status:");
        $this->log('info', 'showCacheStatus started');

        $tasks = [
            'stock_and_prices'      => 15,
            'automatic_investments' => 60,
            'stock_news'            => 360,
            'cleanup'               => 1440,
        ];

        $nowTs = now()->timestamp;

        foreach ($tasks as $taskName => $intervalMinutes) {
            $cacheKey = "cron:last_{$taskName}_run";
            $lastRunTs = $this->readLastRun($cacheKey);

            if ($lastRunTs) {
                $elapsedMin = max(0, intdiv($nowTs - $lastRunTs, 60));
                $nextRunIn = max(0, $intervalMinutes - $elapsedMin);
                $status = $nextRunIn > 0 ? "⏳ Next in {$nextRunIn}min" : "✅ Ready to run";

                $this->info("  • {$taskName}: Last run {$elapsedMin}min ago - {$status}");
                $this->log('info', 'cache status', [
                    'task'          => $taskName,
                    'last_run_ts'   => $lastRunTs,
                    'elapsed_min'   => $elapsedMin,
                    'next_run_in'   => $nextRunIn,
                ]);
            } else {
                $this->info("  • {$taskName}: ✅ Never run - Ready to run");
                $this->log('info', 'cache status', [
                    'task'          => $taskName,
                    'last_run_ts'   => null,
                    'elapsed_min'   => null,
                    'next_run_in'   => 0,
                ]);
            }
        }

        $this->log('info', 'showCacheStatus completed');
        $this->info("");
    }

    /**
     * Normalize cache value to unix timestamp (int) or null.
     */
    private function readLastRun(string $cacheKey): ?int
    {
        $v = Cache::get($cacheKey);
        if ($v === null) return null;

        if ($v instanceof \DateTimeInterface) {
            return $v->getTimestamp();
        }
        if (is_numeric($v)) {
            return (int) $v;
        }
        try {
            return Carbon::parse($v)->getTimestamp();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Centralized logger: writes to daily cron log with run_id, and warns/errors echo to console.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $context = array_merge([
            'run_id' => $this->runId,
            'action' => $this->argument('action'),
        ], $context);

        // File log
        Log::channel('cron')->{$level}($message, $context);

        // Mirror warnings/errors to console for manual runs
        if ($level === 'error') {
            $this->error($message);
        } elseif ($level === 'warning') {
            $this->warn($message);
        }
    }
}
