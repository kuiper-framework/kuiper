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

namespace kuiper\db;

interface ConnectionPoolInterface
{
    /**
     * @return ConnectionInterface
     */
    public function take(): ConnectionInterface;

    /**
     * @param ConnectionInterface $connection
     *
     * @return void
     */
    public function release(ConnectionInterface $connection): void;
}
