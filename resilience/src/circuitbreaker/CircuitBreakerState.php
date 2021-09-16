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

namespace kuiper\resilience\circuitbreaker;

interface CircuitBreakerState
{
    public function tryAcquirePermission(): bool;

    public function acquirePermission(): void;

    public function releasePermission(): void;

    public function onError(int $duration, \Exception $exception): void;

    public function onSuccess(int $duration): void;

    public function attempts(): int;

    public function getState(): State;
}
