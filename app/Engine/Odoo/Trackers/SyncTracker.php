<?php

namespace App\Engine\Odoo\Trackers;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks which Odoo sync topics are currently running.
 *
 * Sync workers call `markStarted()` and `markCompleted()`.
 * Livewire components can check `syncing()` on mount to restore
 * the spinning/loading button state.
 */
class SyncTracker
{
    private const CACHE_KEY = 'odoo.sync_tracker.topics';

    /**
     * Mark a sync topic as actively running.
     */
    public static function markStarted(string $topic): void
    {
        Cache::put(
            self::cacheKey($topic),
            true,
            now()->addMinutes(10),
        );
    }

    /**
     * Mark a sync topic as completed.
     */
    public static function markCompleted(string $topic): void
    {
        Cache::forget(self::cacheKey($topic));
    }

    /**
     * Check if a sync topic is currently running.
     */
    public static function syncing(string $topic): bool
    {
        return (bool) Cache::get(self::cacheKey($topic), false);
    }

    /**
     * Return all currently active sync topics.
     *
     * @return array<string>
     */
    public static function activeSyncs(): array
    {
        $keys = Cache::get(config('cache.prefix').':odoo.sync_tracker.topics.*', []);

        if (empty($keys)) {
            return [];
        }

        return array_map(static fn (string $key): string => str_replace(
            config('cache.prefix').':odoo.sync_tracker.topic.',
            '',
            $key,
        ), is_array($keys) ? $keys : [$keys]);
    }

    private static function cacheKey(string $topic): string
    {
        return config('cache.prefix').":odoo.sync_tracker.topic.{$topic}";
    }
}
