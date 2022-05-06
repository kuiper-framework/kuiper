<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\Closable;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\transporter\Session;
use kuiper\rpc\transporter\TransporterInterface;

class RpcClient implements RpcRequestHandlerInterface, Closable
{
    public function __construct(
        private readonly TransporterInterface $transporter,
        private readonly RpcResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * @return TransporterInterface
     */
    public function getTransporter(): TransporterInterface
    {
        return $this->transporter;
    }

    public function close(): void
    {
        $this->transporter->close();
    }

    /**
     * @return RpcResponseFactoryInterface
     */
    public function getResponseFactory(): RpcResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        $session = $this->transporter->createSession($request);
        try {
            if ($request instanceof HasRequestIdInterface) {
                try {
                    return $this->createResponse($request, $session);
                } catch (RequestIdMismatchException $e) {
                    while (true) {
                        try {
                            return $this->createResponse($request, $session);
                        } catch (RequestIdMismatchException $e) {
                            // noOp
                        }
                    }
                }
            } else {
                return $this->createResponse($request, $session);
            }
        } finally {
            $session->close();
        }
    }

    protected function createResponse($request, Session $session): RpcResponseInterface
    {
        return $this->responseFactory->createResponse($request, $session->recv());
    }
}
