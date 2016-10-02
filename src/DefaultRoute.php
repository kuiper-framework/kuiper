<?php
namespace kuiper\web;

class DefaultRoute implements RouteInterface
{
    public function __construct(array $options)
    {
    }
    
    /**
     * @inheritDoc
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @inheritDoc
     */
    public function getCallback()
    {
        return $this->calback;
    }

    /**
     * @inheritDoc
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->
    }
}