<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Stock;
use App\Models\StockNews;
use App\Models\ApiRequestLog;
use App\Services\StockDataService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessStockNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private ?string $symbol;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $symbol = null)
    {
        $this->symbol = $symbol;
    }

    /**
     * Execute the job.
     */
    public function handle(StockDataService $stockDataService): void
    {
        // info logs suppressed; only log errors

        try {
            if ($this->symbol) {
                // Process news for specific symbol
                $this->processNewsForSymbol($this->symbol, $stockDataService);
            } else {
                // Process news for all active stocks
                $stocks = Stock::where('is_active', true)->get();
                
                foreach ($stocks as $stock) {
                    try {
                        $this->processNewsForSymbol($stock->symbol, $stockDataService);
                        sleep(2); // 2 seconds delay between stocks for rate limiting
                    } catch (\Exception $e) {
                        Log::error("Failed to process news for {$stock->symbol}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('ProcessStockNewsJob failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process news for a specific symbol
     */
    private function processNewsForSymbol(string $symbol, StockDataService $stockDataService): void
    {
        $startTime = microtime(true);

        // Get news from API
        $news = $stockDataService->getStockNews($symbol, 10);
        
        if (!$news || empty($news)) {
            // info/warning logs suppressed; only log errors
            return;
        }

        $savedCount = 0;
        $skippedCount = 0;

        foreach ($news as $article) {
            try {
                // Check if we already have this article
                $existing = StockNews::where('symbol', $symbol)
                    ->where('headline', $article['headline'])
                    ->where('published_at', Carbon::parse($article['datetime']))
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                // Calculate simple sentiment score based on keywords
                $sentimentScore = $this->calculateSentimentScore($article['headline'] . ' ' . ($article['summary'] ?? ''));

                // Create news record
                StockNews::create([
                    'symbol' => $symbol,
                    'headline' => $article['headline'],
                    'summary' => $article['summary'] ?? null,
                    'content' => $article['content'] ?? null,
                    'url' => $article['url'] ?? null,
                    'source' => $article['source'] ?? null,
                    'author' => $article['author'] ?? null,
                    'image_url' => $article['image'] ?? null,
                    'published_at' => Carbon::parse($article['datetime']),
                    'fetched_at' => Carbon::now(),
                    'is_featured' => $this->isFeaturedArticle($article),
                    'sentiment_score' => $sentimentScore,
                ]);

                $savedCount++;

            } catch (\Exception $e) {
                Log::error("Failed to save news article for {$symbol}", [
                    'headline' => $article['headline'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Log API request
        $responseTime = round((microtime(true) - $startTime) * 1000);
        ApiRequestLog::create([
            'api_provider' => 'finnhub',
            'endpoint' => 'news',
            'symbol' => $symbol,
            'response_time_ms' => $responseTime,
            'success' => true,
            'rate_limit_type' => 'general',
            'requested_at' => Carbon::now(),
        ]);

        // info logs suppressed; only log errors
    }

    /**
     * Calculate simple sentiment score based on keywords
     */
    private function calculateSentimentScore(string $text): float
    {
        $positiveWords = [
            'surge', 'jump', 'rise', 'gain', 'up', 'positive', 'growth', 'profit', 'earnings', 'beat',
            'strong', 'bullish', 'rally', 'climb', 'soar', 'spike', 'boost', 'increase', 'higher'
        ];

        $negativeWords = [
            'drop', 'fall', 'decline', 'down', 'negative', 'loss', 'miss', 'weak', 'bearish', 'crash',
            'plunge', 'slide', 'dip', 'lower', 'decrease', 'concern', 'risk', 'worry', 'fear'
        ];

        $text = strtolower($text);
        $words = str_word_count($text, 1);
        
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($words as $word) {
            if (in_array($word, $positiveWords)) {
                $positiveCount++;
            } elseif (in_array($word, $negativeWords)) {
                $negativeCount++;
            }
        }

        $total = $positiveCount + $negativeCount;
        if ($total === 0) return 0;

        return round(($positiveCount - $negativeCount) / $total, 2);
    }

    /**
     * Determine if an article should be featured
     */
    private function isFeaturedArticle(array $article): bool
    {
        $headline = strtolower($article['headline'] ?? '');
        $summary = strtolower($article['summary'] ?? '');

        $featuredKeywords = [
            'earnings', 'quarterly', 'results', 'report', 'announcement', 'acquisition', 'merger',
            'ceo', 'executive', 'leadership', 'strategy', 'innovation', 'breakthrough'
        ];

        foreach ($featuredKeywords as $keyword) {
            if (str_contains($headline, $keyword) || str_contains($summary, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessStockNewsJob failed', [
            'symbol' => $this->symbol,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
