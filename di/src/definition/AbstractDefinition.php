<?php

namespace kuiper\di\definition;

use kuiper\di\Scope;

abstract class AbstractDefinition implements DefinitionInterface
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @return string
     */
    public function getScope()
    {
        if ($this->scope === null) {
            return Scope::SINGLETON;
        }

        return $this->scope;
    }

    public function scope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
