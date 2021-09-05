<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class PatchMapping extends RequestMapping
{
    public $method = 'PATCH';
}
