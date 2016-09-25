<?php
namespace kuiper\cache;

use Psr\Cache\CacheItemInterface;

interface ItemInterface extends CacheItemInterface
{
    /**
     * @param PoolInterface $pool
     */
    public function setPool(PoolInterface $pool);

    /**
     * @param string $key
     */
    public function setKey($key);

    /**
     * @return array
     */
    public function getKeyPath();

    /**
     * @return int the expiration time as timestamp, <=0 means value never expires
     */
    public function getExpiration();

    /**
     * @param int $seconds seconds before expired to precompute cache data
     */
    public function setPrecomputeTime($seconds);

    /**
     * mark item is miss
     */
    public function miss();

    /**
     * @return bool
     */
    public function save();

    /**
     * @return bool
     */
    public function clear();

    /**
     * @return bool
     */
    public function unlock();
}
