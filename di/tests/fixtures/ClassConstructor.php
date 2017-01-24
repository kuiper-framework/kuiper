<?php

namespace kuiper\di\fixtures;

use stdClass;

class ClassConstructor
{
    public $param1;
    public $param2;

    public function __construct(stdClass $param1, $param2)
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}
