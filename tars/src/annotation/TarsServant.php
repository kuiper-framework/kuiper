<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class TarsServant implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @Required
     *
     * @var string
     */
    public $value;
}
