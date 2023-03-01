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

namespace kuiper\swoole\server;

use kuiper\swoole\ConnectionInfo;
use RuntimeException;

class DummyServer extends AbstractServer
{
    protected function doStart(): void
    {
        throw new RuntimeException('Cannot start dummy server');
    }

    public function reload(): void
    {
        throw new RuntimeException('Cannot start dummy server');
    }

    public function stop(): void
    {
        throw new RuntimeException('Cannot start dummy server');
    }

    public function getMasterPid(): int
    {
        return 0;
    }

    public function send(int $clientId, string $data): void
    {
    }

    public function isTaskWorker(): bool
    {
        return false;
    }

    public function task(mixed $data, int $taskWorkerId = -1, callable $onFinish = null)
    {
        return null;
    }

    public function finish(mixed $data): void
    {
    }

    public function tick(int $millisecond, callable $callback): int
    {
        return 0;
    }

    public function after(int $millisecond, callable $callback): int
    {
        return 0;
    }

    public function getConnectionInfo(int $clientId): ?ConnectionInfo
    {
        return null;
    }

    public function stats(): array
    {
        return [];
    }
}
