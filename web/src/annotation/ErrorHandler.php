<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ErrorHandler implements ComponentInterface
{
    use ComponentTrait;

    /**
     * Exception class name, can be string or string[].
     *
     * @var mixed
     */
    public $value;
}
