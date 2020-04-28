<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

class Bar
{
    public $name;

    /**
     * Bar constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
