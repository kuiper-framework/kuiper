<?php
namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class AnnotationTargetClass
{
    public $data;
    public $name;
    public $target;
}
