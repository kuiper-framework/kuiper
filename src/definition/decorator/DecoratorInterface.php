<?php

namespace kuiper\di\definition\decorator;

use kuiper\di\DefinitionEntry;

interface DecoratorInterface
{
    /**
     * @param DefinitionEntry $entry
     *
     * @return DefinitionEntry
     */
    public function decorate(DefinitionEntry $entry);
}
