<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class JsonRpcService implements ComponentInterface
{
    use ComponentTrait;
}
