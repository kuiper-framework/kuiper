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

namespace kuiper\jsonrpc\client;

use kuiper\rpc\RpcMethodInterface;

class NoOutParamJsonRpcResponseFactory extends JsonRpcResponseFactory
{
    protected function buildResult(RpcMethodInterface $method, $result): array
    {
        return parent::buildResult($method, [$result]);
    }
}
