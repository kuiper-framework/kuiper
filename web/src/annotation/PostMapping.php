<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class PostMapping extends RequestMapping
{
    public $method = ['POST'];
}
