<?php

namespace kuiper\cache\driver;

use RuntimeException;

class Apc extends AbstractDriver implements DriverInterface
{
    use PathVersionKeyMaker;

    public function __construct()
    {
        if (!function_exists('apcu_fetch')) {
            throw new RuntimeException("extension 'apcu' (version >= 5.0.0) is required to use apc cache");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch($key)
    {
        return apcu_fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = apcu_fetch($keys);
        $arr = [];
        foreach ($keys as $key) {
            $arr[] = isset($values[$key]) ? $values[$key] : false;
        }

        return $arr;
    }

    /**
     * {@inheritdoc}
     */
    protected function store($key, $value, $ttl)
    {
        return apcu_store($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    protected function delete($key)
    {
        return apcu_delete($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function incr($key)
    {
        $version = apcu_inc($key);
        if ($version === false) {
            apcu_store($key, $version = 1);
        }

        return $version;
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $key, $ttl)
    {
        $lockKey = $this->makeLockKey($key);

        return apcu_add($lockKey, 1, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $key)
    {
        return apcu_delete($this->makeLockKey($key));
    }

    protected function makeLockKey($key)
    {
        return md5('_lock:'.implode('#', $key));
    }
}
