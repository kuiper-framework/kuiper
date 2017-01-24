<?php

namespace kuiper\cache\driver;

/**
 * This class provides a NULL caching driver.
 */
class None implements DriverInterface
{
    public function setPrefix($prefix)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function mget(array $keys)
    {
        return array_fill(0, count($keys), false);
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $key, $data, $expiration)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function del(array $key)
    {
        return true;
    }

    /**
     * {@inheritdoc}
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $key)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return true;
    }
}
