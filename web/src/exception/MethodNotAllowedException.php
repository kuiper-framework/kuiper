<?php
namespace kuiper\web\exception;

class MethodNotAllowedException extends HttpException
{
    /**
     * HTTP methods allowed
     *
     * @var string[]
     */
    protected $allowedMethods;

    /**
     * @var int
     */
    protected $statusCode = 405;

    /**
     * Get allowed methods
     *
     * @return string[]
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
