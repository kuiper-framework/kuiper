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

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequest;
use Psr\Http\Message\RequestInterface as HttpRequestInterface;

class JsonRpcServerRequest extends RpcRequest implements JsonRpcRequestInterface
{
    public function __construct(
        HttpRequestInterface $httpRequest,
        RpcMethodInterface $rpcMethod,
        private readonly int $requestId,
        private readonly string $version)
    {
        parent::__construct($httpRequest, $rpcMethod);
    }

    public function getRequestId(): int
    {
        return $this->requestId;
    }

    public function getJsonRpcVersion(): string
    {
        return $this->version;
    }
}
