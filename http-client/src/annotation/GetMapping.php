<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class GetMapping extends RequestMapping
{
    public $method = 'GET';
}
