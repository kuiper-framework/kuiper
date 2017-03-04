<?php

namespace kuiper\web;

interface ErrorHandlerInterface extends RequestAwareInterface, ResponseAwareInterface
{
    /**
     * Handles the exception.
     */
    public function handle($e);
}
