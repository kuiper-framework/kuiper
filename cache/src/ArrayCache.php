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

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * An in-memory cache storage which implements PSR-16.
 */
class ArrayCache implements CacheInterface
{
    public const KEY_DATA = 0;
    public const KEY_EXPIRE = 1;

    private array $values = [];

    /**
     * @var callable
     */
    private $timeFactory;

    public function __construct(
        private readonly int $ttl = 60,
        private readonly int $capacity = 256,
        private readonly float $fillRate = 0.6,
        callable|string $timeFactory = 'time')
    {
        $this->timeFactory = $timeFactory;
    }

    private function currentTime(): int
    {
        return call_user_func($this->timeFactory);
    }

    protected function purge(): void
    {
        $count = count($this->values);
        $now = $this->currentTime();
        foreach ($this->values as $itemKey => $item) {
            if ($now > $item[self::KEY_EXPIRE]) {
                unset($this->values[$itemKey]);
                --$count;
            }
        }

        if ($count > $this->capacity) {
            $this->values = array_slice($this->values, -1 * (int) ($this->capacity * $this->fillRate));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $result = $this->values[$key] ?? null;
        if (isset($result) && $this->currentTime() < $result[self::KEY_EXPIRE]) {
            return $result[self::KEY_DATA];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = [
            self::KEY_DATA => $value,
            self::KEY_EXPIRE => $this->currentTime() + ($ttl ?? $this->ttl),
        ];
        if (count($this->values) > $this->capacity) {
            $this->purge();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
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
        $result = $this->values[$key] ?? null;

        return isset($result) && $this->currentTime() < $result[self::KEY_EXPIRE];
    }
}
