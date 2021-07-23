<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcRpcResponse;
use Psr\Http\Message\ResponseInterface;

class HttpJsonResponseFactory implements RpcResponseFactoryInterface
{
    /**
     * @var RpcResponseNormalizer
     */
    private $normalizer;

    /**
     * DefaultResponseParser constructor.
     */
    public function __construct(RpcResponseNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): \kuiper\rpc\RpcResponseInterface
    {
        try {
            $request->getInvokingMethod()->setResult($this->buildResult($request->getInvokingMethod(), $response));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcRpcResponse($request, $response);
    }

    protected function buildResult(InvokingMethod $method, ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('content-type');
        if (false !== stripos($contentType, 'application/json')) {
            $data = json_decode((string) $response->getBody(), true);

            return $this->normalizer->normalize($method, [$data]);
        }

        return [null];
    }
}
