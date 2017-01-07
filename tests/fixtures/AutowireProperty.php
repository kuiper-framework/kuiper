<?php

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Autowired;
use kuiper\di\annotation\Inject;

/**
 * Fixture class for the Autowiring tests.
 *
 * @Autowired
 */
class AutowireProperty
{
    /**
     * @var AnnotationFixture2
     */
    private $property1;

    private $property2;

    /**
     * @Inject("foo")
     */
    private $property3;

    public function setProperty2(AnnotationFixture2 $value)
    {
        $this->property2 = $value;
    }

    public function setProperty3(AnnotationFixture2 $value)
    {
        $this->property3 = $value;
    }
}
