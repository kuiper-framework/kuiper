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

namespace kuiper\tracing;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class RateLimiter
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var CacheItemInterface
     */
    private $balance;

    /**
     * @var CacheItemInterface
     */
    private $lastTick;

    /**
     * @var float
     */
    private $creditsPerNanosecond = 0;

    /**
     * @var float
     */
    private $maxBalance = 0;

    /**
     * RateLimiter constructor.
     *
     * @param CacheItemPoolInterface $cache
     * @param string                 $currentBalanceKey key of current balance value in $cache
     * @param string                 $lastTickKey       key of last tick value in $cache
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        string $currentBalanceKey,
        string $lastTickKey
    ) {
        $this->cache = $cache;
        $this->balance = $this->cache->getItem($currentBalanceKey);
        $this->lastTick = $this->cache->getItem($lastTickKey);
    }

    /**
     * @param float $itemCost
     *
     * @return bool
     */
    public function checkCredit(float $itemCost): bool
    {
        if ($this->creditsPerNanosecond <= 0) {
            return false;
        }

        [$lastTick, $balance] = $this->getState();

        if (!$lastTick) {
            $this->saveState(hrtime(true), 0);

            return true;
        }

        $currentTick = hrtime(true);
        $elapsedTime = $currentTick - $lastTick;
        $balance += $elapsedTime * $this->creditsPerNanosecond;
        if ($balance > $this->maxBalance) {
            $balance = $this->maxBalance;
        }

        $result = false;
        if ($balance >= $itemCost) {
            $balance -= $itemCost;
            $result = true;
        }

        $this->saveState($currentTick, $balance);

        return $result;
    }

    /**
     * Initializes limiter costs and boundaries.
     *
     * @param float $creditsPerNanosecond
     * @param float $maxBalance
     */
    public function initialize(float $creditsPerNanosecond, float $maxBalance): void
    {
        $this->creditsPerNanosecond = $creditsPerNanosecond;
        $this->maxBalance = $maxBalance;
    }

    /**
     * Method loads last tick and current balance from cache.
     *
     * @return array [$lastTick, $balance]
     */
    private function getState(): array
    {
        return [
            $this->lastTick->get(),
            $this->balance->get(),
        ];
    }

    /**
     * Method saves last tick and current balance into cache.
     *
     * @param int   $lastTick
     * @param float $balance
     */
    private function saveState(int $lastTick, float $balance): void
    {
        $this->lastTick->set($lastTick);
        $this->balance->set($balance);
        $this->cache->saveDeferred($this->lastTick);
        $this->cache->saveDeferred($this->balance);
        $this->cache->commit();
    }
}
