<?php

namespace kuiper\web;

use kuiper\web\exception\HttpException;
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
        if ($e instanceof HttpException) {
            return $e->getResponse();
        } else {
            return $this->getResponse()->withStatus(500);
        }
    }
}
