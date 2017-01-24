<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetClass;

/**
 * @AnnotationTargetClass("Some data")
 */
class ClassWithInvalidAnnotationTargetAtMethod
{

    /**
     * @AnnotationTargetClass("functionName")
     */
    public function functionName($param)
    {
    }
}
