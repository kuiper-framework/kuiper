<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\pool\PoolInterface;

class RedisProxy
{
    /**
     * @var PoolInterface
     */
    private $redisPool;

    public function __construct(PoolInterface $redisPool)
    {
        $this->redisPool = $redisPool;
    }

    public function __call(string $method, array $args)
    {
        return $this->redisPool->take()->{$method}(...$args);
    }

    public function hscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
        return $this->redisPool->take()->hscan($strKey, $iIterator, $strPattern, $iCount);
    }

    public function scan(&$iIterator, $strPattern = null, $iCount = null)
    {
        return $this->redisPool->take()->scan($iIterator, $strPattern, $iCount);
    }

    public function sscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
        return $this->redisPool->take()->sscan($strKey, $iIterator, $strPattern, $iCount);
    }

    public function zscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
        return $this->redisPool->take()->zscan($strKey, $iIterator, $strPattern, $iCount);
    }
}
