<?php

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
