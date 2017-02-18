<?php
namespace kuiper\web\exception;

class CsrfTokenInvalidException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 400;
}
