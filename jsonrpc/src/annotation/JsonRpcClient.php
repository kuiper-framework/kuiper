<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class JsonRpcClient implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $protocol;

    /**
     * @var bool
     */
    public $outParams;
}
