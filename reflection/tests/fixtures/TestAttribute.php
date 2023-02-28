<?php

declare(strict_types=1);

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 0.6
 */

namespace demo\integration\demo;

use kuiper\jsonrpc\attribute\JsonRpcClient;

#[JsonRpcClient("CalculatorObj", "", "winwin.demo")]
interface CalculatorServant
{
    public function add(int $a, int $b): int;

}
