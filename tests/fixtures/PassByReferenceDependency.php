<?php

namespace kuiper\di\fixtures;

use stdClass;

class PassByReferenceDependency
{
    public function __construct(stdClass &$object)
    {
        $object->foo = 'bar';
    }
}
