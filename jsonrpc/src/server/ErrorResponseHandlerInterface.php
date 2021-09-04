<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;

interface ErrorResponseHandlerInterface
{
    /**
     * @param \Exception                   $exception
     * @param JsonRpcRequestInterface|null $request
     *
     * @return string
     */
    public function handle(\Exception $exception, JsonRpcRequestInterface $request = null): string;
}
