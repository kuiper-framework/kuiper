<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use Webmozart\Assert\Assert;

class ErrorResponseHandler
{
    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    public function __construct(ExceptionNormalizer $exceptionNormalizer)
    {
        $this->exceptionNormalizer = $exceptionNormalizer;
    }

    public function handle(\Exception $exception, JsonRpcRequestInterface $request = null): string
    {
        if ($exception instanceof JsonRpcRequestException) {
            return $this->createRequestErrorResponse($exception);
        }
        Assert::notNull($request);

        return $this->createErrorResponse($exception, $request);
    }

    private function createRequestErrorResponse(JsonRpcRequestException $e): string
    {
        return JsonRpcProtocol::encode([
            'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
            'id' => $e->getRequestId(),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ],
        ]);
    }

    private function createErrorResponse(\Exception $e, JsonRpcRequestInterface $rpcRequest): string
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
}
