<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class DeleteMapping extends RequestMapping
{
    public $method = 'DELETE';
}
