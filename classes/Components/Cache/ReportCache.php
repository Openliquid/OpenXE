<?php

namespace Xentral\Components\Cache;

use ApplicationCore;
use RuntimeException;

/**
 * Simple cache wrapper that supports APCu and Redis backends.
 * Used to cache expensive report queries such as sales numbers.
 */
class ReportCache
{
    /** @var ApplicationCore */
    private $app;

    /** @var string */
    private $backend;

    /** @var \Redis|null */
    private $redis;

    /**
     * @param ApplicationCore $app
     * @param array           $options ['backend' => 'redis'|'apcu', 'host' => '', 'port' => 6379]
     */
    public function __construct(ApplicationCore $app, array $options = [])
    {
        $this->app = $app;
        $this->backend = $options['backend'] ?? (function_exists('apcu_fetch') ? 'apcu' : 'redis');

        if ($this->backend === 'redis') {
            if (!extension_loaded('redis')) {
                throw new RuntimeException('Redis extension not available');
            }
            $host = $options['host'] ?? '127.0.0.1';
            $port = $options['port'] ?? 6379;
            $this->redis = new \Redis();
            $this->redis->connect($host, $port);
        }
    }

    /**
     * Retrieve cached value by key.
     * Runs hook `report_cache_hit` or `report_cache_miss` for monitoring.
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function get(string $key)
    {
        $value = null;
        if ($this->backend === 'redis') {
            $value = $this->redis->get($key);
        } elseif ($this->backend === 'apcu') {
            $success = false;
            $value = apcu_fetch($key, $success);
            if (!$success) {
                $value = null;
            }
        }

        if ($value !== null) {
            $this->app->erp->RunHook('report_cache_hit', 1, $key);
        } else {
            $this->app->erp->RunHook('report_cache_miss', 1, $key);
        }

        return $value;
    }

    /**
     * Store value in cache
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   Lifetime in seconds
     *
     * @return void
     */
    public function set(string $key, $value, int $ttl = 3600): void
    {
        if ($this->backend === 'redis') {
            $this->redis->setex($key, $ttl, serialize($value));
        } elseif ($this->backend === 'apcu') {
            apcu_store($key, $value, $ttl);
        }
    }

    /**
     * Convenience wrapper to remember a value for given key.
     *
     * @param string   $key
     * @param int      $ttl
     * @param callable $callback
     *
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return is_string($cached) && $this->backend === 'redis' ? unserialize($cached) : $cached;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }
}
