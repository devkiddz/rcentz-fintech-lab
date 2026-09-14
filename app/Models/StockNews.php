<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'headline',
        'summary',
        'content',
        'url',
        'source',
        'author',
        'image_url',
        'published_at',
        'fetched_at',
        'is_featured',
        'sentiment_score',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'is_featured' => 'boolean',
        'sentiment_score' => 'decimal:2',
    ];

    /**
     * Get latest news for a symbol
     */
    public static function getLatestNews(string $symbol, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('symbol', $symbol)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured news
     */
    public static function getFeaturedNews(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_featured', true)
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get news by sentiment
     */
    public static function getNewsBySentiment(string $symbol, float $minSentiment = null, float $maxSentiment = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('symbol', $symbol);
        
        if ($minSentiment !== null) {
            $query->where('sentiment_score', '>=', $minSentiment);
        }
        
        if ($maxSentiment !== null) {
            $query->where('sentiment_score', '<=', $maxSentiment);
        }
        
        return $query->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sentiment label
     */
    public function getSentimentLabelAttribute(): string
    {
        if (!$this->sentiment_score) return 'Neutral';
        
        if ($this->sentiment_score >= 0.5) return 'Very Positive';
        if ($this->sentiment_score >= 0.1) return 'Positive';
        if ($this->sentiment_score >= -0.1) return 'Neutral';
        if ($this->sentiment_score >= -0.5) return 'Negative';
        return 'Very Negative';
    }

    /**
     * Get sentiment color
     */
    public function getSentimentColorAttribute(): string
    {
        if (!$this->sentiment_score) return 'text-gray-600';
        
        if ($this->sentiment_score >= 0.5) return 'text-green-600';
        if ($this->sentiment_score >= 0.1) return 'text-blue-600';
        if ($this->sentiment_score >= -0.1) return 'text-gray-600';
        if ($this->sentiment_score >= -0.5) return 'text-orange-600';
        return 'text-red-600';
    }

    /**
     * Get formatted published date
     */
    public function getFormattedPublishedDateAttribute(): string
    {
        return $this->published_at ? $this->published_at->diffForHumans() : 'Unknown';
    }

    /**
     * Get excerpt from content
     */
    public function getExcerptAttribute(): string
    {
        $text = $this->summary ?: $this->content ?: '';
        return strlen($text) > 150 ? substr($text, 0, 150) . '...' : $text;
    }
}
