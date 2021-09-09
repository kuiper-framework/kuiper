<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class TarsClient extends TarsServant
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $service;
}
