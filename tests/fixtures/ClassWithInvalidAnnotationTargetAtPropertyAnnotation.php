<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;
use kuiper\annotations\fixtures\annotation\AnnotationTargetClass;

/**
 * @AnnotationTargetClass("Some data")
 */
class ClassWithInvalidAnnotationTargetAtPropertyAnnotation
{
    /**
     * @AnnotationTargetAnnotation("Foo")
     */
    public $bar;
}
