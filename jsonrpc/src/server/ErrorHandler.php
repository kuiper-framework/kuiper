<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

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
use Webmozart\Assert\Assert;

class ErrorHandler implements InvalidRequestHandlerInterface, ErrorHandlerInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, ExceptionNormalizer $exceptionNormalizer)
    {
        $this->exceptionNormalizer = $exceptionNormalizer;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function handleInvalidRequest(RequestInterface $request, \Exception $exception): ResponseInterface
    {
        return $this->createResponse($this->createRequestErrorResponse($exception));
    }

    public function handle(RpcRequestInterface $request, \Throwable $error): RpcResponseInterface
    {
        Assert::isInstanceOf($request, JsonRpcRequestInterface::class);
        /** @var JsonRpcRequestInterface $request */
        return new RpcResponse($request, $this->createResponse($this->createErrorResponse($error, $request)));
    }

    private function createRequestErrorResponse(\Exception $e): string
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

    private function createErrorResponse(\Throwable $e, JsonRpcRequestInterface $rpcRequest): string
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

    private function createResponse(string $body): ResponseInterface
    {
        return $this->responseFactory->createResponse(400)
            ->withBody($this->streamFactory->createStream($body));
    }
}
