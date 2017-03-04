<?php

namespace kuiper\web\exception;

class UnauthorizedException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 401;
}
