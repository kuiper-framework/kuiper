<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

use kuiper\rpc\annotation\CircuitBreaker;
use kuiper\rpc\annotation\Retry;

/**
 * @CircuitBreaker()
 */
interface HelloService
{
    /**
     * @Retry()
     */
    public function hello(string $name): string;
}
