<?php

declare(strict_types=1);

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerInterface
{
    /**
     * Initialize controller.
     * if return false, stop execute action
     * if return ResponseInterface, stop execute action and return the response.
     *
     * @return false|ResponseInterface|null
     */
    public function initialize();

    /**
     * Sets request.
     */
    public function setRequest(ServerRequestInterface $request): void;

    /**
     * Sets response.
     */
    public function setResponse(ResponseInterface $response): void;

    /**
     * Gets the response.
     */
    public function getResponse(): ResponseInterface;
}
