<?php
namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target("ALL")
 */
final class AnnotationEnum
{
    const ONE   = 1;
    const TWO   = 2;
    const THREE = 3;

    /**
     * @var mixed
     *
     * @Enum({"ONE","TWO"})
     */
    public $value;
}
