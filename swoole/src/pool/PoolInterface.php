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

namespace kuiper\swoole\pool;

use kuiper\swoole\exception\PoolClosedException;
use kuiper\swoole\exception\PoolTimeoutException;

interface PoolInterface
{
    /**
     * @return mixed the connection
     *
     * @throws PoolTimeoutException|PoolClosedException
     */
    public function take(): mixed;

    /**
     * Release the connection.
     *
     * @param mixed $connection
     */
    public function release(mixed $connection): void;

    /**
     * Close the pool.
     */
    public function close(): void;

    /**
     * Gets the pool name.
     *
     * @return string
     */
    public function getName(): string;
}
