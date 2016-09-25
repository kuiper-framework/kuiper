<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\AnnotationWithVarType;
use kuiper\annotations\fixtures\annotation\AnnotationTargetAnnotation;

class ClassWithAnnotationWithInvalidVarTypeProp
{
    /**
     * @AnnotationWithVarType(integer = "abc")
     */
    public $invalidProperty;
}