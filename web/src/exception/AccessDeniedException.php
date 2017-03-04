<?php

namespace kuiper\web\exception;

class AccessDeniedException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 403;
}
