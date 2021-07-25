<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;

class JsonRpcResponseFactory extends SimpleJsonRpcResponseFactory
{
    /**
     * @var RpcResponseNormalizer
     */
    private $normalizer;
    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    /**
     * JsonRpcSerializerResponseFactory constructor.
     */
    public function __construct(RpcResponseNormalizer $normalizer, ExceptionNormalizer $exceptionNormalizer)
    {
        $this->normalizer = $normalizer;
        $this->exceptionNormalizer = $exceptionNormalizer;
    }

    protected function handleError(JsonRpcRequestInterface $request, int $code, string $message, $data): RpcResponseInterface
    {
        if (null === $data) {
            return parent::handleError($request, $code, $message, $data);
        }
        throw $this->exceptionNormalizer->denormalize($data, '');
    }

    protected function buildResult(RpcMethodInterface $method, $result): array
    {
        return $this->normalizer->normalize($method, $result);
    }
}
