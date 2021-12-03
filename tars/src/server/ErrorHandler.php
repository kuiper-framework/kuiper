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

namespace kuiper\tars\server;

use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\client\TarsResponse;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ErrorHandler implements ErrorHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * ErrorHandler constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function handle(RpcRequestInterface $request, \Throwable $error): RpcResponseInterface
    {
        if ($error instanceof \InvalidArgumentException) {
            $this->logger->info(sprintf('process %s#%s failed: %s',
                $request->getRpcMethod()->getTargetClass(), $request->getRpcMethod()->getMethodName(), $error));
        } else {
            $this->logger->error(sprintf('process %s#%s failed: %s',
                $request->getRpcMethod()->getTargetClass(), $request->getRpcMethod()->getMethodName(), $error));
        }
        /** @var TarsRequestInterface $request */
        $packet = ResponsePacket::createFromRequest($request);
        if ($error->getCode() > 0) {
            $packet->iRet = $error->getCode();
        } elseif ($error instanceof \InvalidArgumentException) {
            $packet->iRet = ErrorCode::INVALID_ARGUMENT;
        } else {
            $packet->iRet = ErrorCode::UNKNOWN;
        }
        $packet->sResultDesc = $error->getMessage();
        $packet->sBuffer = '';

        $response = $this->responseFactory->createResponse(500);
        $response->getBody()->write((string) $packet->encode());

        return new TarsResponse($request, $response, $packet);
    }
}
