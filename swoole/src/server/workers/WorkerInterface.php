<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

interface WorkerInterface
{
    public function getPid(): int;

    public function getWorkerId(): int;

    public function start(): void;

    public function getChannel(): SocketChannel;
}
