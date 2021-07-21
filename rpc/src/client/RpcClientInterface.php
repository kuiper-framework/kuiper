<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\exception\CommunicationException;
use kuiper\rpc\RequestInterface;

interface RpcClientInterface extends RequestFactoryInterface
{
    /**
     * Send request and parse response.
     *
     * @throws CommunicationException
     */
    public function sendRequest(RequestInterface $request): array;
}
