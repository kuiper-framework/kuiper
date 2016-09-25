<?php
namespace kuiper\cache;

use Psr\Cache\CacheItemPoolInterface;
use kuiper\cache\driver\DriverInterface;

interface PoolInterface extends CacheItemPoolInterface
{
    /**
     * @param string $key
     * @param callable $resolver
     * @param array $options array may contains option
     *  - lifetime
     *  - lock_ttl
     *  - precompute_time
     * @return mixed
     */
    public function remember($key, callable $resolver, array $options = []);

    /**
     * @param array $options array may contains option
     *  - lifetime
     *  - precompute_time
     *  - prefix
     *  - serializer
     *  - namespace_separator
     * @return static
     */
    public function setOptions(array $options);

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * @param DriverInterface $driver
     * @return static
     */
    public function setDriver(DriverInterface $driver);

    /**
     * @return DriverInterface
     */
    public function getDriver();

    /**
     * @param string $class
     */
    public function setItemClass($class);
}
