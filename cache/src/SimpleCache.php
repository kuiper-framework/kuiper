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

namespace kuiper\cache;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * PSR-6 cache to PSR-16 Adapter.
 */
class SimpleCache implements CacheInterface
{
    public function __construct(private readonly CacheItemPoolInterface $pool)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null): mixed
    {
        $cacheItem = $this->pool->getItem($key);

        return $cacheItem->isHit() ? $cacheItem->get() : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        $cacheItem = $this->pool->getItem($key);
        $cacheItem->set($value);
        if (null !== $ttl) {
            $cacheItem->expiresAfter($ttl);
        }

        return $this->pool->save($cacheItem);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null): iterable
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }

        $items = $this->pool->getItems($keys);

        $values = [];

        foreach ($items as $key => $item) {
            $values[$key] = $item->isHit() ? $item->get() : $default;
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $valuesIsArray = \is_array($values);
        if (!$valuesIsArray && !$values instanceof Traversable) {
            throw new InvalidArgumentException(sprintf('Cache values must be array or Traversable, "%s" given.', get_debug_type($values)));
        }
        $ok = true;
        foreach ($values as $key => $value) {
            $item = $this->pool->getItem((string) $key);
            $item->set($value);
            if (null !== $ttl) {
                $item->expiresAfter($ttl);
            }
            $ok = $this->pool->saveDeferred($item) && $ok;
        }

        return $this->pool->commit() && $ok;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys): bool
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }

        return $this->pool->deleteItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        return $this->pool->hasItem($key);
    }
}
