<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;
use kuiper\annotations\fixtures\annotation\AnnotationWithVarType;

class ClassWithAnnotationWithInvalidVarTypeProp
{
    /**
     * @AnnotationWithVarType(integer = "abc")
     */
    public $invalidProperty;
}
