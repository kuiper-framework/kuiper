<?php

namespace kuiper\di\definition\decorator;

use kuiper\di\DefinitionEntry;

class DummyDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function decorate(DefinitionEntry $entry)
    {
        return $entry;
    }
}
