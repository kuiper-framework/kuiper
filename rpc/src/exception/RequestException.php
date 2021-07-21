<?php

declare(strict_types=1);

namespace kuiper\rpc\exception;

use kuiper\rpc\RequestInterface;

class RequestException extends \Exception
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * InvalidRequestException constructor.
     */
    public function __construct(RequestInterface $request, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
