<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

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
