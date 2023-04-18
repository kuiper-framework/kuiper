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
use kuiper\rpc\RpcServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class JsonRpcServerRequest extends RpcServerRequest implements JsonRpcRequestInterface
{
    public function __construct(
        ServerRequestInterface $httpRequest,
        RpcMethodInterface $rpcMethod,
        private readonly int $requestId,
        private readonly string $version,
        private readonly ?string $extendedVersion = null
    ) {
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

    /**
     * @return string|null
     */
    public function getExtendedVersion(): ?string
    {
        return $this->extendedVersion;
    }
}
