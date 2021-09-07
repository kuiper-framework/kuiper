<?php

declare(strict_types=1);

namespace kuiper\rpc\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class RpcClient implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var string
     */
    public $protocol;
}
