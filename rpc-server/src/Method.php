<?php

namespace kuiper\rpc\server;

class Method implements MethodInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var callable
     */
    private $callable;

    public function __construct($id, $callable)
    {
        $this->id = $id;
        $this->callable = $callable;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getCallable()
    {
        return $this->callable;
    }
}
