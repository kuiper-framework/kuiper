<?php
namespace kuiper\di\definition;

/**
 * Helps to define alias
 */
class AliasDefinition implements DefinitionInterface
{
    use ScopeTrait;
    
    /**
     * @var string
     */
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }
}
