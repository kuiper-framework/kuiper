<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface extends RequestAwareInterface, ResponseAwareInterface
{
    /**
     * @return ServerRequestInterface
     */
    public function getRequest();

    /**
     * @return ResponseInterface
     */
    public function getResponse();

    /**
     * Initialize controller.
     *
     * @return bool
     */
    public function initialize();
}
