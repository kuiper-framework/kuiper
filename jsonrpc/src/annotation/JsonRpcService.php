<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\annotation;

use kuiper\di\annotation\Service;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class JsonRpcService extends Service
{
    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $version;
}
