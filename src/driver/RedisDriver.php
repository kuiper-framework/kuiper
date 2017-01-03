<?php

namespace kuiper\cache\driver;

abstract class RedisDriver extends AbstractDriver
{
    use PathVersionKeyMaker;

    /**
     * @var object
     */
    protected $connection;

    /**
     * @var array
     */
    protected $cacheVersions;

    public function getConnection()
    {
        return $this->connection;
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
    public function lock(array $path, $ttl)
    {
        $key = $this->makeLockKey($path);
        $redis = $this->getConnection();
        $success = $redis->setnx($key, time());
        if ($success) {
            $redis->expire($key, $ttl);
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $path)
    {
        return $this->getConnection()->del($this->makeLockKey($path));
    }

    public function clearCacheVersions()
    {
        $this->cacheVersions = [];
    }

    protected function makeLockKey(array $path)
    {
        return $this->prefix.$this->transformKey(sprintf('_lock%s%s', $this->separator, implode($this->separator, $path)));
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch($key)
    {
        $ret = $this->getConnection()->get($key);

        return $ret === false ? false : unserialize($ret);
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = $this->getConnection()->mget($keys);
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $value === false ? false : unserialize($value);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    protected function store($key, $value, $ttl)
    {
        if ($ttl >= 1) {
            $this->getConnection()->setex($key, $ttl, serialize($value));
        } else {
            $this->getConnection()->set($key, serialize($value));
        }

        return true;
    }

    protected function delete($key)
    {
        return $this->getConnection()->del($key);
    }

    /**
     * {@inheritdoc}
     */
    protected function incr($key)
    {
        if (isset($this->cacheVersions[$key])) {
            ++$this->cacheVersions[$key];
        }

        return $this->getConnection()->incr($key);
    }

    protected function getCacheVersion($key)
    {
        if (isset($this->cacheVersions[$key])) {
            return $this->cacheVersions[$key];
        } else {
            return $this->cacheVersions[$key] = $this->getConnection()->get($key) ?: 0;
        }
    }
}
