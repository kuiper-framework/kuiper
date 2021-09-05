<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\fixtures\service;

use kuiper\jsonrpc\annotation\JsonRpcService;

/**
 * @JsonRpcService()
 */
class CalculatorServiceImpl implements CalculatorService
{
    /**
     * {@inheritDoc}
     */
    public function add($a, $b)
    {
        return $a + $b;
    }
}
