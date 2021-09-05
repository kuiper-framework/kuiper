<?php

declare(strict_types=1);

namespace kuiper\http\client\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class OptionsMapping extends RequestMapping
{
    public $method = 'OPTIONS';
}
