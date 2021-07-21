<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\RpcClient;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

class JsonRpcClient extends RpcClient
{
    /**
     * {@inheritDoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        try {
            return parent::handle($request);
        } catch (RequestIdMismatchException $e) {
            do {
                try {
                    return $this->getResponseFactory()->createResponse($request, $this->getTransporter()->recv());
                } catch (RequestIdMismatchException $e) {
                    // pass
                }
            } while (true);
        }
    }
}
