<?php
namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsrfTokenInvalidException extends HttpException
{
}
