<?php

namespace kuiper\cache\driver;

interface DriverInterface
{
    /**
     * @param string $prefix
     */
    public function setPrefix($prefix);

    /**
     * Returns the stored data as well as its expiration time.
     *
     * the data array contains keys:
     *  - data
     *  - expiration
     *
     * @param array $path
     *
     * @return array|false the array contains two keys
     */
    public function get(array $path);

    /**
     * Returns the stored data as well as its expiration time.
     *
     * @param array $paths
     *
     * @return array
     */
    public function mget(array $paths);

    /**
     * @param array $path
     * @param mixed $data
     * @param int   $expiration the expiration time as timestamp
     *
     * @return bool
     */
    public function set(array $path, $data, $expiration);

    /**
     * @param array $path
     *
     * @return bool
     */
    public function del(array $path);

    /**
     * @return bool
     */
    public function clear();

    /**
     * @param array $path
     * @param int   $ttl
     *
     * @return bool
     */
    public function lock(array $path, $ttl);

    /**
     * @param array $path
     *
     * @return bool
     */
    public function unlock(array $path);
}
