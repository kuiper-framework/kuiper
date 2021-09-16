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

namespace kuiper\swoole\server\workers;

interface WorkerInterface
{
    public function getPid(): int;

    public function getWorkerId(): int;

    public function start(): void;

    public function getChannel(): SocketChannel;

    /**
     * Adds timer.
     */
    public function tick(int $millisecond, callable $callback): int;

    public function after(int $millisecond, callable $callback): int;
}
