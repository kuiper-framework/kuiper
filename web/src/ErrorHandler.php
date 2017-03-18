<?php

namespace kuiper\web;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ErrorHandler implements ErrorHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait, ResponseAwareTrait, RequestAwareTrait;

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function handle($e)
    {
        $this->logger && $this->logger->error(sprintf("Uncaught exception %s %s:\n%s", get_class($e), $e->getMessage(), $e->getTraceAsString()));

        return $this->response;
    }
}
