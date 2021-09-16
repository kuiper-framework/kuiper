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
    /**
     * @var int
     */
    private $requestId;
    /**
     * @var string
     */
    private $version;

    public function __construct(HttpRequestInterface $httpRequest, RpcMethodInterface $rpcMethod, int $requestId, string $version)
    {
        parent::__construct($httpRequest, $rpcMethod);
        $this->requestId = $requestId;
        $this->version = $version;
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
