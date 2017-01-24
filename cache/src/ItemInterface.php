<?php

namespace kuiper\cache;

use Psr\Cache\CacheItemInterface;

interface ItemInterface extends CacheItemInterface
{
    /**
     * @param PoolInterface $pool
     * @param string        $key
     */
    public function __construct(PoolInterface $pool, $key);

    /**
     * Gets the path.
     *
     * @return array
     */
    public function getKeyPath();

    /**
     * @return int the expiration time as timestamp, <=0 means value never expires
     */
    public function getExpiration();

    /**
     * @param int $seconds  seconds before expired to precompute cache data
     * @param int $lock_ttl seconds to release lock after lock acquired
     */
    public function setPrecomputeTime($seconds, $lock_ttl);

    /**
     * mark item is miss.
     */
    public function miss();

    /**
     * @return bool
     */
    public function save();

    /**
     * @return bool
     */
    public function delete();

    /**
     * @return bool
     */
    public function unlock();
}
