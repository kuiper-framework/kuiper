<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\inject;

/**
 * Used to check that child classes also have the injections of the parent classes.
 */
class AnnotationFixtureChild extends AnnotationFixtureParent
{
    /**
     * @Inject("foo")
     */
    protected $propertyChild;

    /**
     * @Inject
     */
    public function methodChild()
    {
    }
}
