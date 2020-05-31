<?php

declare(strict_types=1);

namespace kuiper\db;

interface ConnectionPoolInterface
{
    public function take(): ConnectionInterface;

    public function release(ConnectionInterface $connection): void;
}
