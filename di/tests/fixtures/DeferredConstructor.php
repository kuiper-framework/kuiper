<?php

namespace kuiper\di\fixtures;

class DeferredConstructor
{
    public function __construct(DummyClass $obj)
    {
    }
}
