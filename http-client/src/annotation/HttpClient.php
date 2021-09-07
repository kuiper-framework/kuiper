<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

use kuiper\rpc\annotation\RpcClient;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class HttpClient extends RpcClient
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $responseParser;
}
