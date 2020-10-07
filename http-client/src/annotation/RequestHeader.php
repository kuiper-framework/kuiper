<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class RequestHeader
{
    /**
     * @var string
     */
    public $value;
}
