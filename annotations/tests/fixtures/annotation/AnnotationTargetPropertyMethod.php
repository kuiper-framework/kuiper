<?php
namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
final class AnnotationTargetPropertyMethod
{
    public $data;
    public $name;
    public $target;
}
