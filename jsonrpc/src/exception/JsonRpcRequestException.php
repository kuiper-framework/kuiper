<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\exception;

class JsonRpcRequestException extends \Exception
{
    /**
     * @var int|null
     */
    private $requestId;

    /**
     * JsonRpcRequestException constructor.
     */
    public function __construct(?int $requestId, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->requestId = $requestId;
    }

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }
}
