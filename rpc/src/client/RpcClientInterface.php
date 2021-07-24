<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\exception\CommunicationException;
use kuiper\rpc\RpcRequestInterface;

interface RpcClientInterface extends RpcRequestFactoryInterface
{
    /**
     * Send request and parse response.
     *
     * @throws CommunicationException
     */
    public function sendRequest(RpcRequestInterface $request): array;
}
