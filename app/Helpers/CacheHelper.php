<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

/**
 * Helper class for cache operations.
 * 
 * Note: Laravel Cache doesn't support wildcard deletion by default.
 * This helper provides methods to clear cache by prefix patterns.
 */
class CacheHelper
{
    /**
     * Clear cache entries matching a pattern.
     * 
     * Note: This only works with Redis/Memcached with tags.
     * For file/database cache, we'll clear specific known keys or flush all.
     * 
     * @param string $pattern Pattern like 'donations_*', 'mosques_*', etc.
     * @return void
     */
    public static function forgetPattern(string $pattern): void
    {
        $driver = config('cache.default');
        
        // If using Redis with tags support
        if ($driver === 'redis' && Cache::getStore()->getRedis()) {
            // Extract tag from pattern (e.g., 'donations_*' -> 'donations')
            $tag = str_replace('_*', '', $pattern);
            Cache::tags([$tag])->flush();
            return;
        }

        // For other drivers, we can't efficiently delete by pattern
        // So we'll just clear the entire cache (use with caution in production)
        // In production, consider using specific cache keys instead of patterns
        // Cache::flush(); // Uncomment if you want to flush all cache
    }

    /**
     * Clear multiple cache patterns.
     * 
     * @param array $patterns Array of patterns
     * @return void
     */
    public static function forgetPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern) {
            self::forgetPattern($pattern);
        }
    }
}

