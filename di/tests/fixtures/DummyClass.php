<?php

namespace kuiper\di\fixtures;

class DummyClass
{
    public static $calls = [];

    private $name;

    public function foo()
    {
        self::$calls[] = __METHOD__;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
