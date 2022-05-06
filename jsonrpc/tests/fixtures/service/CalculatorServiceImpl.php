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


use kuiper\jsonrpc\attribute\JsonRpcService;

#[JsonRpcService]
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
