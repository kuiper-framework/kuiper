<?php

namespace kuiper\web\exception;

class CsrfTokenException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 400;
}
