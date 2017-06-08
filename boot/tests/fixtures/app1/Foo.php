<?php

namespace kuiper\boot\fixtures\app1;

use kuiper\di\annotation\Inject;

class Foo
{
    /**
     * @Inject("foo")
     *
     * @var string
     */
    private $foo;

    public function getFoo()
    {
        return $this->foo;
    }

    public function setFoo($foo)
    {
        $this->foo = $foo;

        return $this;
    }
}
