<?php

namespace kuiper\rpc\server;

class ErrorContext
{
    /**
     * @var object
     */
    private $invoker;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * ErrorContext constructor.
     *
     * @param object     $invoker
     * @param string     $method
     * @param array      $parameters
     * @param \Exception $error
     */
    public function __construct($invoker, $method, array $parameters, \Exception $error)
    {
        $this->invoker = $invoker;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->exception = $error;
    }

    /**
     * @return object
     */
    public function getInvoker()
    {
        return $this->invoker;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
