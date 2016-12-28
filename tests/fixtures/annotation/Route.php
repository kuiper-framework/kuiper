<?php
namespace kuiper\annotations\fixtures\annotation;

/** @Annotation */
class Route
{
    /** @var string @Required */
    public $pattern;
    public $name;
}
