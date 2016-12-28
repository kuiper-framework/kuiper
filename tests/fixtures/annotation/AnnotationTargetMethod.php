<?php
namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class AnnotationTargetMethod
{
    public $data;
    public $name;
    public $target;
}
