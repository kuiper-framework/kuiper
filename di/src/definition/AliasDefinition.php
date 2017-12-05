<?php

namespace kuiper\di\definition;

class AliasDefinition extends AbstractDefinition
{
    /**
     * @var string
     */
    private $alias;

    public function __construct($alias)
    {
        $this->alias = ltrim($alias, '\\');
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
