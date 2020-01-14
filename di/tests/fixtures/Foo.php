<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

class Foo
{
    private $name;

    /**
     * Foo constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
