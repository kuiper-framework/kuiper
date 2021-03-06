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

use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\transporter\Receivable;
use kuiper\rpc\transporter\TransporterInterface;

class RpcClient implements RpcRequestHandlerInterface
{
    /**
     * @var TransporterInterface
     */
    private $transporter;

    /**
     * @var RpcResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * AbstractRpcClient constructor.
     *
     * @param TransporterInterface        $transporter
     * @param RpcResponseFactoryInterface $responseFactory
     */
    public function __construct(TransporterInterface $transporter, RpcResponseFactoryInterface $responseFactory)
    {
        $this->transporter = $transporter;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return TransporterInterface
     */
    public function getTransporter(): TransporterInterface
    {
        return $this->transporter;
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
        if ($request instanceof HasRequestIdInterface) {
            try {
                return $this->send($request);
            } catch (RequestIdMismatchException $e) {
                if ($this->transporter instanceof Receivable) {
                    while (true) {
                        try {
                            return $this->responseFactory->createResponse($request, $this->transporter->recv());
                        } catch (RequestIdMismatchException $e) {
                            // noOp
                        }
                    }
                } else {
                    throw $e;
                }
            }
        } else {
            return $this->send($request);
        }
    }

    private function send(RpcRequestInterface $request): RpcResponseInterface
    {
        return $this->responseFactory->createResponse($request, $this->transporter->sendRequest($request));
    }
}
