<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\StockQuote;
use App\Models\StockPriceHistory;
use App\Models\StockNews;
use App\Models\ApiRequestLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupOldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting CleanupOldDataJob');

        try {
            $results = [
                'stock_quotes' => $this->cleanupStockQuotes(),
                'stock_price_history' => $this->cleanupStockPriceHistory(),
                'stock_news' => $this->cleanupStockNews(),
                'api_request_logs' => $this->cleanupApiRequestLogs(),
            ];

            Log::info('CleanupOldDataJob completed', $results);

        } catch (\Exception $e) {
            Log::error('CleanupOldDataJob failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cleanup old stock quotes (keep last 30 days)
     */
    private function cleanupStockQuotes(): array
    {
        $cutoff = Carbon::now()->subDays(30);
        $deleted = StockQuote::where('fetched_at', '<', $cutoff)->delete();
        
        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoff->toDateString()
        ];
    }

    /**
     * Cleanup old stock price history (keep last 90 days)
     */
    private function cleanupStockPriceHistory(): array
    {
        $cutoff = Carbon::now()->subDays(90);
        $deleted = StockPriceHistory::where('timestamp', '<', $cutoff)->delete();
        
        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoff->toDateString()
        ];
    }

    /**
     * Cleanup old stock news (keep last 60 days)
     */
    private function cleanupStockNews(): array
    {
        $cutoff = Carbon::now()->subDays(60);
        $deleted = StockNews::where('fetched_at', '<', $cutoff)->delete();
        
        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoff->toDateString()
        ];
    }

    /**
     * Cleanup old API request logs (keep last 30 days)
     */
    private function cleanupApiRequestLogs(): array
    {
        $cutoff = Carbon::now()->subDays(30);
        $deleted = ApiRequestLog::where('requested_at', '<', $cutoff)->delete();
        
        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoff->toDateString()
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupOldDataJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
