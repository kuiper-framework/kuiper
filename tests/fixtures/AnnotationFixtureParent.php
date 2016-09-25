<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\inject;

/**
 * Used to check that child classes also have the injections of the parent classes.
 */
class AnnotationFixtureParent
{
    /**
     * @Inject("foo")
     */
    protected $propertyParent;

    /**
     * @Inject("foo")
     */
    private $propertyParentPrivate;

    /**
     * @Inject
     */
    public function methodParent()
    {
    }
}
