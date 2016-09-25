<?php
namespace kuiper\cache\driver;

/**
 * This class provides a NULL caching driver
 */
class None implements DriverInterface
{
    /**
     * @inheritDoc
     */
    public function get(array $key)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function mget(array $keys)
    {
        return array_fill(0, count($keys), false);
    }

    /**
     * @inheritDoc
     */
    public function set(array $key, $data, $expiration)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function del(array $key)
    {
        return true;
    }

    /**
     * @inheritDoc
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
        return true;
    }

    /**
     * @inheritDoc
     */
    public function unlock(array $key)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function isAvailable()
    {
        return true;
    }
}
