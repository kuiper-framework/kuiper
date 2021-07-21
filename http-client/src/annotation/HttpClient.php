<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class HttpClient
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $path;
}
