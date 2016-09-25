<?php
namespace kuiper\annotations\fixtures\annotation;

/**
 * @Annotation
 */
class DummyJoinTable
{
    public $name;
    public $joinColumns;
    public $inverseJoinColumns;
}
