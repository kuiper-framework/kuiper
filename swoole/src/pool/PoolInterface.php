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

use kuiper\swoole\exception\PoolTimeoutException;

interface PoolInterface
{
    /**
     * @return mixed the connection
     *
     * @throws PoolTimeoutException
     */
    public function take();

    /**
     * reset current connection.
     */
    public function reset(): void;

    public function getName(): string;
}
