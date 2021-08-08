<?php

declare(strict_types=1);

namespace kuiper\annotations\fixtures;

use kuiper\annotations\AnnotationTrait;

/**
 * @Annotation
 */
class Foo
{
    use AnnotationTrait;

    /**
     * @var string
     */
    public $bar;
}
