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

    /**
     * {@inheritdoc}
     */
    public function handle($exception)
    {
        $this->logger && $this->logger->error(sprintf("Uncaught exception %s %s:\n%s", get_class($exception), $exception->getMessage(), $exception->getTraceAsString()));

        return $this->response;
    }
}
