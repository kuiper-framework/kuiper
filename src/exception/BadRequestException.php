<?php
namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class BadRequestException extends HttpException
{
    public function __construct($message, ServerRequestInterface $request, ResponseInterface $response)
    {
        RuntimeException::__construct($message);
        $this->request = $request;
        $this->response = $response;
    }
}
