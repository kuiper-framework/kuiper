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
     * if return false, stop execute action
     * if return ResponseInterface, stop execute action and return the response.
     *
     * @return bool|ResponseInterface
     */
    public function initialize();
}
