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
     * @param mixed $data
     * @param float $timeout
     */
    public function push($data, float $timeout = null): bool;

    /**
     * @param float $timeout
     *
     * @return mixed|false return false if not available
     */
    public function pop(float $timeout = null);

    public function size(): int;
}
