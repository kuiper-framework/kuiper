<?php

namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target("ALL")
 */
final class AnnotationWithVarType
{
    /**
     * @var mixed
     */
    public $mixed;

    /**
     * @var bool
     */
    public $boolean;

    /**
     * @var bool
     */
    public $bool;

    /**
     * @var float
     */
    public $float;

    /**
     * @var string
     */
    public $string;

    /**
     * @var int
     */
    public $integer;

    /**
     * @var array
     */
    public $array;

    /**
     * @var AnnotationTargetAll
     */
    public $annotation;

    /**
     * @var int[]
     */
    public $arrayOfIntegers;

    /**
     * @var string[]
     */
    public $arrayOfStrings;

    /**
     * @var AnnotationTargetAll[]
     */
    public $arrayOfAnnotations;
}
