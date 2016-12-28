<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;
use kuiper\annotations\fixtures\annotation\AnnotationWithVarType;

class ClassWithAnnotationWithInvalidVarType
{
    /**
     * @AnnotationWithVarType(annotation = @AnnotationTargetAnnotation)
     */
    public function invalidMethod()
    {
    }
}
