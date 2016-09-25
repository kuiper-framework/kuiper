<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationWithVarType;
use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;

class ClassWithAnnotationWithInvalidVarType
{
    /**
     * @AnnotationWithVarType(annotation = @AnnotationTargetAnnotation)
     */
    public function invalidMethod(){}
}