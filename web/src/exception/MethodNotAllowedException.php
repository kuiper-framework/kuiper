<?php

namespace kuiper\web\exception;

class MethodNotAllowedException extends HttpException
{
    /**
     * HTTP methods allowed.
     *
     * @var string[]
     */
    protected $allowedMethods;

    /**
     * @var int
     */
    protected $statusCode = 405;

    public function __construct(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get allowed methods.
     *
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
