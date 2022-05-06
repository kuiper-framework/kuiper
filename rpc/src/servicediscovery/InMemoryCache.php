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

namespace kuiper\rpc\servicediscovery;

use Psr\SimpleCache\CacheInterface;

class InMemoryCache implements CacheInterface
{
    public const KEY_DATA = 'data';
    public const KEY_EXPIRES = 'expires';
    public const DEFAULT_TTL = 60;

    /**
     * @var array
     */
    private array $table = [];

    public function __construct(private readonly int $ttl = self::DEFAULT_TTL)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $result = $this->table[$key] ?? null;
        if (null !== $result && time() < $result[self::KEY_EXPIRES]) {
            return $result[self::KEY_DATA];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->table[$key] = [
            self::KEY_DATA => $value,
            self::KEY_EXPIRES => time() + ($ttl ?? $this->ttl),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->table[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->table = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }
}
