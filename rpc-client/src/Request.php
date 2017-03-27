<?php

namespace kuiper\rpc\client;

class Request extends \kuiper\rpc\Request
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters;

    public function __construct($class, $method, array $params)
    {
        $this->class = $class;
        $this->method = $method;
        $this->parameters = $params;
        parent::__construct('');
    }

    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function withParameters(array $parameters)
    {
        $new = clone $this;
        $new->parameters = $parameters;

        return $new;
    }
}
