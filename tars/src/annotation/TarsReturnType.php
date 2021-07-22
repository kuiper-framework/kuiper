<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
final class TarsReturnType
{
    /**
     * @Required()
     *
     * @var string
     */
    public $value;
}
