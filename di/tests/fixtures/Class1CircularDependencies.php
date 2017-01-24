<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Inject;

/**
 * Fixture class for testing circular dependencies.
 */
class Class1CircularDependencies
{
    /**
     * @Inject
     *
     * @var Class2CircularDependencies
     */
    public $class2;
}
