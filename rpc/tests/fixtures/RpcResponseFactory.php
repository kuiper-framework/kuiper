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

namespace kuiper\rpc\fixtures;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use phpDocumentor\Reflection\Types\Integer;
use Psr\Http\Message\ResponseInterface;

class RpcResponseFactory implements RpcResponseFactoryInterface
{
    private mixed $result = null;

    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        $request = $request->withRpcMethod($request->getRpcMethod()->withResult($this->result));

        return new RpcResponse($request, $response);
    }
}
