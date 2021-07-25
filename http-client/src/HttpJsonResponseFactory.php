<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
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

    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        try {
            $method = $request->getRpcMethod()->withResult($this->buildResult($request->getRpcMethod(), $response));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcResponse($request->withRpcMethod($method), $response);
    }

    protected function buildResult(RpcMethodInterface $method, ResponseInterface $response): array
    {
        $contentType = $response->getHeaderLine('content-type');
        if (false !== stripos($contentType, 'application/json')) {
            $data = json_decode((string) $response->getBody(), true);

            return $this->normalizer->normalize($method, [$data]);
        }

        return [null];
    }
}
