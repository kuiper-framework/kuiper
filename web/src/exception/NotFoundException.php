<?php

namespace kuiper\web\exception;

class NotFoundException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 404;
}
