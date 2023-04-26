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

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    public const DEFAULT_TTL = 300;

    private string $key;

    private mixed $value;
    private ?DateTimeInterface $expiration;

    private DriverInterface $driver;

    private ?bool $hit = null;

    public function __construct(string $key, DriverInterface $driver)
    {
        $this->key = $key;
        $this->driver = $driver;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        if (null !== $this->hit) {
            return $this->value;
        }
        $data = $this->driver->getData($this->key);
        $now = time();
        if (isset($data['expiration']) && $data['expiration'] > $now) {
            $this->value = $data['data']['return'];
            $this->hit = true;
        } else {
            $this->value = null;
            $this->hit = false;
        }

        return $this->value;
    }

    public function isHit(): bool
    {
        $this->get();

        return $this->hit;
    }

    public function save(): bool
    {
        if (null !== $this->expiration) {
            $expiration = $this->expiration->getTimestamp();
        } else {
            $expiration = time() + self::DEFAULT_TTL;
        }

        return $this->driver->storeData($this->key, [
            'return' => $this->value,
            'createOn' => time(),
        ], $expiration);
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        $this->hit = true;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function expiresAfter(DateInterval|int|null $time): static
    {
        if (null === $time) {
            $this->expiration = null;

            return $this;
        }
        if ($time instanceof DateInterval) {
            $dateInterval = $time;
        } else {
            $dateInterval = new DateInterval('PT'.abs($time).'S');
            $dateInterval->invert = ($time > 0 ? 0 : 1);
        }

        return $this->expiresAt((new DateTimeImmutable())->add($dateInterval));
    }
}
