<?php

namespace kuiper\di\definition;

class NamedParameters
{
    /**
     * @var array
     */
    private $parameters;

    public function __construct(array $params)
    {
        $this->parameters = $params;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
