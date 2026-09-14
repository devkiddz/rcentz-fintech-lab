<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\CleanupOldDataJob;
use App\Jobs\FetchStockHistoryJob;
use App\Jobs\ProcessAutomaticNavUpdatesJob;
use App\Jobs\ProcessStockNewsJob;
use App\Jobs\UpdateStockQuotesJob;

class CronController extends Controller
{
    /**
     * Security token to prevent unauthorized access
     */
    private function validateToken(Request $request)
    {
        $token = (string) ($request->header('X-Cron-Token') ?: $request->query('token', ''));
        $expectedToken = (string) config('app.cron_token', '');

        // Fail closed when CRON_TOKEN is not configured.
        if ($expectedToken === '' || $token === '' || ! hash_equals($expectedToken, $token)) {
            abort(403, 'Unauthorized access');
        }
    }

    /**
     * Run all cron jobs
     */
    public function runAll(Request $request)
    {
        $this->validateToken($request);
        
        try {
            $results = [];
            
            // Dispatch all jobs
            UpdateStockQuotesJob::dispatch();
            $results[] = 'UpdateStockQuotesJob dispatched';
            
            FetchStockHistoryJob::dispatch();
            $results[] = 'FetchStockHistoryJob dispatched';
            
            ProcessStockNewsJob::dispatch();
            $results[] = 'ProcessStockNewsJob dispatched';
            
            ProcessAutomaticNavUpdatesJob::dispatch();
            $results[] = 'ProcessAutomaticNavUpdatesJob dispatched';
            
            CleanupOldDataJob::dispatch();
            $results[] = 'CleanupOldDataJob dispatched';
            
            Log::info('All cron jobs dispatched successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => 'All cron jobs dispatched successfully',
                'jobs' => $results,
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch cron jobs: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to dispatch jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update stock quotes
     */
    public function updateStockQuotes(Request $request)
    {
        $this->validateToken($request);
        
        try {
            UpdateStockQuotesJob::dispatch();
            Log::info('UpdateStockQuotesJob dispatched');
            
            return response()->json([
                'status' => 'success',
                'message' => 'UpdateStockQuotesJob dispatched successfully',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch UpdateStockQuotesJob: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch stock history
     */
    public function fetchStockHistory(Request $request)
    {
        $this->validateToken($request);
        
        try {
            FetchStockHistoryJob::dispatch();
            Log::info('FetchStockHistoryJob dispatched');
            
            return response()->json([
                'status' => 'success',
                'message' => 'FetchStockHistoryJob dispatched successfully',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch FetchStockHistoryJob: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process stock news
     */
    public function processStockNews(Request $request)
    {
        $this->validateToken($request);
        
        try {
            ProcessStockNewsJob::dispatch();
            Log::info('ProcessStockNewsJob dispatched');
            
            return response()->json([
                'status' => 'success',
                'message' => 'ProcessStockNewsJob dispatched successfully',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ProcessStockNewsJob: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process automatic NAV updates
     */
    public function processNavUpdates(Request $request)
    {
        $this->validateToken($request);
        
        try {
            ProcessAutomaticNavUpdatesJob::dispatch();
            Log::info('ProcessAutomaticNavUpdatesJob dispatched');
            
            return response()->json([
                'status' => 'success',
                'message' => 'ProcessAutomaticNavUpdatesJob dispatched successfully',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ProcessAutomaticNavUpdatesJob: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cleanup old data
     */
    public function cleanupOldData(Request $request)
    {
        $this->validateToken($request);
        
        try {
            CleanupOldDataJob::dispatch();
            Log::info('CleanupOldDataJob dispatched');
            
            return response()->json([
                'status' => 'success',
                'message' => 'CleanupOldDataJob dispatched successfully',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch CleanupOldDataJob: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get cron job status and information
     */
    public function status(Request $request)
    {
        $this->validateToken($request);
        
        return response()->json([
            'status' => 'active',
            'available_jobs' => [
                'update-stock-quotes' => 'Updates stock quotes from API',
                'fetch-stock-history' => 'Fetches historical stock data',
                'process-stock-news' => 'Processes and updates stock news',
                'process-nav-updates' => 'Processes automatic NAV updates',
                'cleanup-old-data' => 'Cleans up old data from database',
                'run-all' => 'Runs all jobs at once'
            ],
            'timestamp' => now()->toDateTimeString(),
            'server_time' => now()->toDateTimeString()
        ]);
    }
}
