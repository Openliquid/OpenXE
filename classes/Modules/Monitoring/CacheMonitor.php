<?php

namespace Xentral\Modules\Monitoring;

use ApplicationCore;

/**
 * Simple monitoring module that counts cache hits and misses.
 * Metrics can be extended to integrate with external monitoring solutions.
 */
class CacheMonitor
{
    /** @var ApplicationCore */
    private $app;

    /** @var int */
    private $hits = 0;

    /** @var int */
    private $misses = 0;

    public function __construct(ApplicationCore $app)
    {
        $this->app = $app;
        $this->app->erp->RegisterHook('report_cache_hit', 'cachemonitor', 'onCacheHit');
        $this->app->erp->RegisterHook('report_cache_miss', 'cachemonitor', 'onCacheMiss');
    }

    /**
     * Hook callback for cache hit
     *
     * @param string $key
     */
    public function onCacheHit($key): void
    {
        $this->hits++;
    }

    /**
     * Hook callback for cache miss
     *
     * @param string $key
     */
    public function onCacheMiss($key): void
    {
        $this->misses++;
    }

    /**
     * @return array{hits:int,misses:int}
     */
    public function getStats(): array
    {
        return ['hits' => $this->hits, 'misses' => $this->misses];
    }
}
