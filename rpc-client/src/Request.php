<?php

namespace kuiper\rpc\client;

class Request extends \kuiper\rpc\Request
{
    /**
     * @var string
     */
    private $class;

    public function __construct($class, $method, array $parameters)
    {
        $this->class = $class;
        parent::__construct('', $method, $parameters);
    }

    public function getClass()
    {
        return $this->class;
    }
}
