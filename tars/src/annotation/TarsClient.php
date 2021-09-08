<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class TarsClient implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $service;
}
