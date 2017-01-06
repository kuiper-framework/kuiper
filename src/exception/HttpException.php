<?php
namespace kuiper\web\exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Http Exception
 */
class HttpException extends RuntimeException
{
    /**
     * A request object
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * A response object to send to the HTTP client
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create new exception
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(ServerRequestInterface $request, ResponseInterface $response, $message = null, $prev = null)
    {
        parent::__construct($message, 0, $prev);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get request
     *
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
