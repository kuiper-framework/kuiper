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

use Psr\SimpleCache\CacheInterface;

/**
 * Chains several cache storage together.
 */
class ChainedCache implements CacheInterface
{
    /**
     * ChainedCache constructor.
     *
     * @param CacheInterface[] $cacheList
     */
    public function __construct(private readonly iterable $cacheList)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->cacheList as $cache) {
            $value = $cache->get($key);
            if (isset($value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($this->cacheList as $cache) {
            $cache->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        foreach ($this->cacheList as $cache) {
            $cache->delete($key);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        foreach ($this->cacheList as $cache) {
            $cache->clear();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        foreach ($this->cacheList as $cache) {
            if ($cache->has($key)) {
                return true;
            }
        }

        return false;
    }
}
