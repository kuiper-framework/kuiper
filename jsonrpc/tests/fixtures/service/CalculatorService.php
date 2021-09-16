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
