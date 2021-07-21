<?php

declare(strict_types=1);

namespace kuiper\rpc\exception;

use kuiper\rpc\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BadResponseException extends \Exception
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * BadResponseException constructor.
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct('Cannot parse response');
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
