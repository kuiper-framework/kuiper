<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetClass;
use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;

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