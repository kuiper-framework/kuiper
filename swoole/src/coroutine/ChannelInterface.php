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

namespace kuiper\swoole\coroutine;

interface ChannelInterface
{
    /**
     * @param mixed      $data
     * @param float|null $timeout
     *
     * @return bool
     */
    public function push(mixed $data, float $timeout = null): bool;

    /**
     * @param float|null $timeout
     *
     * @return mixed return false if not available
     */
    public function pop(float $timeout = null): mixed;

    /**
     * Gets the number of elements in the channel.
     *
     * @return int
     */
    public function size(): int;
}
