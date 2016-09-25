<?php
namespace kuiper\cache\driver;

interface DriverInterface
{
    /**
     * Returns the stored data as well as its expiration time
     *
     * @param array $key
     * @return array|false the array contains two keys
     *  - data
     *  - expiration
     */
    public function get(array $key);

    /**
     * Returns the stored data as well as its expiration time
     *
     * @param array $keys
     * @return array
     */
    public function mget(array $keys);

    /**
     * @param array $key
     * @param mixed $data
     * @param int $expiration the expiration time as timestamp
     * @return bool
     */
    public function set(array $key, $data, $expiration);

    /**
     * @param array $key
     * @return bool
     */
    public function del(array $key);

    /**
     * @return bool
     */
    public function clear($prefix);

    /**
     * @return bool
     */
    public function lock(array $key, $ttl);

    /**
     * @return bool
     */
    public function unlock(array $key);
}
