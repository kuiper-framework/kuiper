<?php

namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\DummyColumn;

abstract class TestPhpDocReaderParent
{
    /**
     * @param int $i
     *
     * @return DummyColumn
     */
    public function foo($i)
    {
    }
}
