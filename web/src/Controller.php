<?php

namespace kuiper\web;

abstract class Controller implements ControllerInterface
{
    use RequestAwareTrait, ResponseAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        return true;
    }
}
