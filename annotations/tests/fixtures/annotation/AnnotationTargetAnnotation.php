<?php

namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class AnnotationTargetAnnotation
{
    public $data;
    public $name;
    public $target;
}
