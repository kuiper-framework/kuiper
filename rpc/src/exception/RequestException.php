<?php

declare(strict_types=1);

namespace kuiper\rpc\exception;

use kuiper\rpc\RpcRequestInterface;

class RequestException extends \Exception
{
    /**
     * @var RpcRequestInterface
     */
    private $request;

    /**
     * InvalidRequestException constructor.
     */
    public function __construct(RpcRequestInterface $request, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->request = $request;
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }
}
