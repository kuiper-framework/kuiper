<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class RequestMapping
{
    /**
     * The path mapping URIs. type is string|string[].
     *
     * @var mixed
     */
    public $value;

    /**
     * The HTTP request methods to map to.
     *
     * @var string
     */
    public $method;
}
