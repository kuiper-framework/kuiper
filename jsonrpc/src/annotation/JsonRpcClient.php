<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\annotation;

use kuiper\rpc\annotation\RpcClient;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class JsonRpcClient extends RpcClient
{
    /**
     * @var bool
     */
    public $outParams;
}
