<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
class RequestMapping
{
    /**
     * The path mapping URIs.
     *
     * @var string
     */
    public $value;

    /**
     * Assign a name to this mapping.
     *
     * @var string
     */
    public $name;

    /**
     * The HTTP request methods to map to.
     *
     * @var string[]
     */
    public $method;
}
