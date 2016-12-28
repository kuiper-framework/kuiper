<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetAll;
use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;
use kuiper\annotations\fixtures\annotation\AnnotationWithVarType;

class ClassWithAnnotationWithVarType
{
    /**
     * @AnnotationWithVarType(string = "String Value")
     */
    public $foo;

    /**
     * @AnnotationWithVarType(integer = 123)
     */
    public $int;

    /**
     * @AnnotationWithVarType(annotation = @AnnotationTargetAll)
     */
    public function bar()
    {
    }
}
