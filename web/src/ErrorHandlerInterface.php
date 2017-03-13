<?php

namespace kuiper\web;

interface ErrorHandlerInterface extends RequestAwareInterface, ResponseAwareInterface
{
    /**
     * Handles the exception.
     *
     * @param \Error $e
     */
    public function handle($e);
}
