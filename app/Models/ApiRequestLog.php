<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_provider',
        'endpoint',
        'symbol',
        'response_time_ms',
        'status_code',
        'success',
        'error_message',
        'request_params',
        'response_data',
        'rate_limit_type',
        'requested_at',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
        'status_code' => 'integer',
        'success' => 'boolean',
        'request_params' => 'array',
        'response_data' => 'array',
        'requested_at' => 'datetime',
    ];

    /**
     * Get API usage statistics
     */
    public static function getUsageStats(string $provider = null, string $rateLimitType = null): array
    {
        $query = static::query();
        
        if ($provider) {
            $query->where('api_provider', $provider);
        }
        
        if ($rateLimitType) {
            $query->where('rate_limit_type', $rateLimitType);
        }

        $total = $query->count();
        $successful = $query->where('success', true)->count();
        $failed = $total - $successful;
        $avgResponseTime = $query->where('success', true)->avg('response_time_ms');

        return [
            'total_requests' => $total,
            'successful_requests' => $successful,
            'failed_requests' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'avg_response_time_ms' => round($avgResponseTime ?? 0, 2),
        ];
    }

    /**
     * Get recent API errors
     */
    public static function getRecentErrors(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('success', false)
            ->whereNotNull('error_message')
            ->orderBy('requested_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get rate limit usage for a specific type
     */
    public static function getRateLimitUsage(string $provider, string $rateLimitType, int $periodSeconds): int
    {
        $from = now()->subSeconds($periodSeconds);
        
        return static::where('api_provider', $provider)
            ->where('rate_limit_type', $rateLimitType)
            ->where('requested_at', '>=', $from)
            ->count();
    }

    /**
     * Clean old logs
     */
    public static function cleanOldLogs(int $days = 30): int
    {
        $cutoff = now()->subDays($days);
        return static::where('requested_at', '<', $cutoff)->delete();
    }
}
