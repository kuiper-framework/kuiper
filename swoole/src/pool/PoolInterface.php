<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

interface PoolInterface
{
    /**
     * @return mixed|false
     */
    public function take();

    public function release($connection): void;

    public function with(callable $callback);
}
