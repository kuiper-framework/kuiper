<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\core;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestInterface;

interface JsonRpcRequestInterface extends RpcRequestInterface, HasRequestIdInterface
{
    public const JSONRPC_VERSION = '2.0';
}
