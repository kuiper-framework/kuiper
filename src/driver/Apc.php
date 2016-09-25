<?php
namespace kuiper\cache\driver;

use RuntimeException;

class Apc implements DriverInterface
{
    public function __construct()
    {
        if (!function_exists('apcu_fetch')) {
            throw new RuntimeException("extension 'apcu' (version >= 5.0.0) is required to use apc cache");
        }
    }

    /**
     * @inheritDoc
     */
    public function get(array $key)
    {
        $cacheKey = $this->makeKey($key);
         // error_log("fetch $cacheKey origin=" .json_encode($key));
        return apcu_fetch($cacheKey);
    }

    /**
     * @inheritDoc
     */
    public function mget(array $keys)
    {
        $realKeys = [];
        foreach ($keys as $i => $key) {
            $realKeys[] = $this->makeKey($key);
        }
        $values = apcu_fetch($realKeys);
        $results = [];
        foreach ($realKeys as $key) {
            $results[] = isset($values[$key]) ? $values[$key] : false;
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function set(array $key, $data, $expiration)
    {
        $value = [
            'data' => $data,
            'expiration' => $expiration
        ];
        if ($expiration === null || $expiration <= 0) {
            $ttl = 0;
        } else {
            $ttl = $expiration - time();
            if ($ttl < 1) {
                return true;
            }
        }
        return apcu_store($this->makeKey($key), $value, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function del(array $key)
    {
        list($pathKey, $realKey) = $this->makeKey($key, true);
        apcu_delete($realKey);
         // error_log("del $realKey origin=". json_encode($key));
        $last = end($key);
        if ($last === null) {
            $version = apcu_inc($pathKey);
            if ($version === false) {
                apcu_store($pathKey, 1);
            }
            // error_log("incr path key $pathKey version=$version ");
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clear($prefix)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function lock(array $key, $ttl)
    {
        $lockKey = $this->makeLockKey($key);
        return apcu_add($lockKey, 1, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function unlock(array $key)
    {
        return apcu_delete($this->makeLockKey($key));
    }

    protected function makeKey($key, $path = false)
    {
        // error_log("make key ".json_encode($key));
        $first = array_shift($key);
        $realKey = '_cache::' . $first;
        $pathKey = null;
        while ($key) {
            $name = array_shift($key);
            $pathKey = md5('_path::' . $realKey);
            if (isset($name)) {
                $cacheVersion = apcu_fetch($pathKey);
                // error_log("path key $pathKey version $cacheVersion");
                $realKey .= '_' . $cacheVersion . '::' . $name;
            }
        }
        $realKey = md5($realKey);
        return $path ? [$pathKey, $realKey] : $realKey;
    }

    protected function makeLockKey($key)
    {
        return md5('_lock:' . implode("#", $key));
    }
}
