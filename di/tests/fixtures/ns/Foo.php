<?php

namespace kuiper\di\fixtures\ns;

use kuiper\di\annotation\Inject;
use kuiper\di\fixtures;

class Foo
{
    /**
     * @Inject
     *
     * @var \kuiper\di\fixtures\DummyClass
     */
    protected $property1;

    /**
     * @Inject
     *
     * @var fixtures\DummyClass
     */
    protected $property2;
}
