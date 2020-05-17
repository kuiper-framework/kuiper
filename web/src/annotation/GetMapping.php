<?php

declare(strict_types=1);

namespace kuiper\web\annotation;

/**
 * @Annotation
 * @Target({"CLASS","METHOD"})
 */
final class GetMapping extends RequestMapping
{
    public $method = ['GET'];
}
