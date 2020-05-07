<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class EventListener implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $value;
}
