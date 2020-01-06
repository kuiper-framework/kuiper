<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Bean
{
    /**
     * @var string
     */
    public $name;
}
