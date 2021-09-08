<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\fixtures\service;

use kuiper\jsonrpc\annotation\JsonRpcClient;

/**
 * @JsonRpcClient()
 */
interface CalculatorService
{
    /**
     * @param int|float $a
     * @param int|float $b
     *
     * @return int|float
     */
    public function add($a, $b);
}
