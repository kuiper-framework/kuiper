<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\rpc\client\ResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\RpcResponse;
use Psr\Http\Message\ResponseInterface;

class JsonResponseFactory implements ResponseFactoryInterface
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

    public function createResponse(RequestInterface $request, ResponseInterface $response): \kuiper\rpc\ResponseInterface
    {
        try {
            $request->getInvokingMethod()->setResult($this->buildResult($request->getInvokingMethod(), $response));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcResponse($request, $response);
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
