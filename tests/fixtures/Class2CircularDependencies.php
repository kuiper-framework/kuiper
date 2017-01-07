<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Inject;

/**
 * Fixture class for testing circular dependencies.
 */
class Class2CircularDependencies
{
    /**
     * @Inject
     *
     * @var Class1CircularDependencies
     */
    public $class1;
}
