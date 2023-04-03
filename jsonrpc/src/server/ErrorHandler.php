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

namespace kuiper\jsonrpc\server;

use Exception;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use Webmozart\Assert\Assert;

class ErrorHandler implements InvalidRequestHandlerInterface, ErrorHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ExceptionNormalizer $exceptionNormalizer)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function handleInvalidRequest(RequestInterface $request, Exception $exception): ResponseInterface
    {
        return $this->createResponse($this->createRequestErrorResponse($exception), 400);
    }

    public function handle(RpcRequestInterface $request, Throwable $error): RpcResponseInterface
    {
        Assert::isInstanceOf($request, JsonRpcRequestInterface::class);
        /** @var JsonRpcRequestInterface|RpcRequestInterface $request */
        return new RpcResponse($request, $this->createResponse($this->createErrorResponse($error, $request)));
    }

    private function createRequestErrorResponse(Exception $e): string
    {
        return JsonRpcProtocol::encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $e instanceof JsonRpcRequestException ? $e->getRequestId() : null,
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ],
        ]);
    }

    private function createErrorResponse(Throwable $e, JsonRpcRequestInterface $rpcRequest): string
    {
        return JsonRpcProtocol::encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $rpcRequest->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => $this->exceptionNormalizer->normalize($e),
            ],
        ]);
    }

    private function createResponse(string $body, int $statusCode = 200): ResponseInterface
    {
        return $this->responseFactory->createResponse($statusCode)
            ->withBody($this->streamFactory->createStream($body));
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @return ExceptionNormalizer
     */
    public function getExceptionNormalizer(): ExceptionNormalizer
    {
        return $this->exceptionNormalizer;
    }
}
