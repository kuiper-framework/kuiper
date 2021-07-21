<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\InvokingMethod;

class JsonRpcResponseFactory extends SimpleJsonRpcResponseFactory
{
    /**
     * @var RpcResponseNormalizer
     */
    private $normalizer;

    /**
     * JsonRpcSerializerResponseFactory constructor.
     */
    public function __construct(RpcResponseNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    protected function buildResult(InvokingMethod $method, $result): array
    {
        return $this->normalizer->normalize($method, $result);
    }
}
