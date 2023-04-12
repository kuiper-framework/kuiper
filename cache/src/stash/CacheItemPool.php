<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\cache\stash;

use ArrayIterator;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * PSR-6 Cache implementation borrows from [Stash](https://github.com/tedious/Stash).
 */
class CacheItemPool implements CacheItemPoolInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $itemClass = CacheItem::class;

    private int $defaultTtl = 300;

    private DriverInterface $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function getItem(string $key): CacheItemInterface
    {
        $item = new $this->itemClass($key, $this->driver);

        $item->expiresAfter($this->defaultTtl);

        return $item;
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $item = $this->getItem($key);
            $items[$item->getKey()] = $item;
        }

        return new ArrayIterator($items);
    }

    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        return $this->driver->clear();
    }

    public function deleteItem(string $key): bool
    {
        return $this->driver->clear($key);
    }

    public function deleteItems(array $keys): bool
    {
        $results = true;
        foreach ($keys as $key) {
            $results = $this->deleteItem($key) && $results;
        }

        return $results;
    }

    public function save(CacheItemInterface $item): bool
    {
        return $item->save();
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    public function commit(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return $this->itemClass;
    }

    /**
     * @param string $itemClass
     */
    public function setItemClass(string $itemClass): void
    {
        $this->itemClass = $itemClass;
    }

    /**
     * @return int
     */
    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    /**
     * @param int $defaultTtl
     */
    public function setDefaultTtl(int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }
}
