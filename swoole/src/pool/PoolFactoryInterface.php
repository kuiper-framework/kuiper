<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

interface PoolFactoryInterface
{
    public function create(callable $connectionFactory): PoolInterface;
}
