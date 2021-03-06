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
    public function decorate(callable $call): callable;

    /**
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function call(callable $call, ...$args);

    public function reset(): void;

    public function getMetrics(): RetryMetrics;
}
