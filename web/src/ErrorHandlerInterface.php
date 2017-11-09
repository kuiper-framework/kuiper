<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;

interface ErrorHandlerInterface extends RequestAwareInterface, ResponseAwareInterface
{
    /**
     * Handles the exception.
     *
     * @param \Error $exception
     *
     * @return ResponseInterface
     */
    public function handle($exception);
}
