<?php

namespace kuiper\di\fixtures;

class AnnotationFixture5
{
    /**
     * @Inject
     *
     * @var foobar
     */
    public $property;

    /**
     * @param foobar $foo
     */
    public function __construct($foo)
    {
    }
}
