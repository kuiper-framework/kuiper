<?php

namespace kuiper\di\fixtures;

/**
 * Fixture class for the Autowiring tests.
 */
class AutowiringFixture
{
    private $property1;

    private $property2;

    public function __construct(DummyClass $param1, $param2 = null)
    {
        $this->property1 = $param1;
        $this->property2 = $param2;
    }
}
