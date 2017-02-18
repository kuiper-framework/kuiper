<?php
namespace kuiper\web\exception;

class BadRequestException extends HttpException
{
    /**
     * @var int
     */
    protected $statusCode = 400;
}
