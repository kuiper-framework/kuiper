<?php

declare(strict_types=1);

namespace kuiper\db;

interface ConnectionPoolInterface
{
    public function take(): ConnectionInterface;
}
