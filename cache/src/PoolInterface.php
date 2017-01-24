<?php

namespace kuiper\cache;

use kuiper\cache\driver\DriverInterface;
use Psr\Cache\CacheItemPoolInterface;

interface PoolInterface extends CacheItemPoolInterface
{
    /**
     * Options may contain keys:
     *  - lifetime
     *  - precompute_time
     *  - prefix
     *  - serializer
     *  - namespace_separator.
     *
     * @param DriverInterface $driver
     * @param array           $options
     */
    public function __construct(DriverInterface $driver, array $options = []);

    /**
     * Options may have keys:
     *  - lifetime
     *  - lock_ttl
     *  - precompute_time.
     *
     * @param string   $key
     * @param callable $resolver
     * @param array    $options
     *
     * @return mixed
     */
    public function remember($key, callable $resolver, array $options = []);

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name);

    /**
     * @return DriverInterface
     */
    public function getDriver();

    /**
     * @param string $class
     */
    public function setItemClass($class);
}
