<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationWithTargetSyntaxError;

/**
 * @AnnotationWithTargetSyntaxError()
 */
class ClassWithAnnotationWithTargetSyntaxError
{
    /**
     * @AnnotationWithTargetSyntaxError()
     */
    public $foo;

    /**
     * @AnnotationWithTargetSyntaxError()
     */
    public function bar()
    {
    }
}
