<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetAll;
use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;
use kuiper\annotations\fixtures\annotation\AnnotationTargetClass;
use kuiper\annotations\fixtures\annotation\AnnotationTargetPropertyMethod;

/**
 * @AnnotationTargetClass("Some data")
 */
class ClassWithValidAnnotationTarget
{

    /**
     * @AnnotationTargetPropertyMethod("Some data")
     */
    public $foo;


    /**
     * @AnnotationTargetAll("Some data",name="Some name")
     */
    public $name;

    /**
     * @AnnotationTargetPropertyMethod("Some data",name="Some name")
     */
    public function someFunction()
    {
    }

    /**
     * @AnnotationTargetAll(@AnnotationTargetAnnotation)
     */
    public $nested;
}
