<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\client\RpcClient;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

class TarsClient extends RpcClient
{
    /**
     * {@inheritDoc}
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface
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
