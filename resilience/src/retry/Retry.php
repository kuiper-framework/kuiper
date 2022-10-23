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

namespace kuiper\resilience\retry;

interface Retry
{
    /**
     * Creates a callable with retry.
     *
     * @param callable $call
     *
     * @return callable
     */
    public function decorate(callable $call): callable;

    /**
     * @param callable $call
     * @param mixed    ...$args
     *
     * @return mixed
     */
    public function call(callable $call, ...$args): mixed;

    /**
     * Gets the last call metric.
     *
     * @return RetryMetrics
     */
    public function getMetrics(): RetryMetrics;
}
