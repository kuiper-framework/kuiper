<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class PutMapping extends RequestMapping
{
    public $method = ['PUT'];
}
