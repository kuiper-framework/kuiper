<?php

namespace kuiper\reflection\fixtures\ns;

use kuiper\reflection\fixtures;

class ImportNs
{
    public function test()
    {
        echo fixtures\DummyClass::class;
    }
}
