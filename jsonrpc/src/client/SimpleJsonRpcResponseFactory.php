<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\ResponseFactoryInterface;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\HasRequestIdInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\RpcResponse;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class SimpleJsonRpcResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createResponse(RequestInterface $request, ResponseInterface $response): \kuiper\rpc\ResponseInterface
    {
        /* @var JsonRpcRequest $request */
        Assert::isInstanceOf($request, HasRequestIdInterface::class,
            'request should implements '.HasRequestIdInterface::class);
        $result = json_decode((string) $response->getBody(), true);
        if (false === $result
            || !isset($result['jsonrpc'])
            || JsonRpcRequest::JSONRPC_VERSION !== $result['jsonrpc']
            || !array_key_exists('id', $result)) {
            throw new BadResponseException($request, $response);
        }
        if (null !== $result['id'] && $result['id'] !== $request->getRequestId()) {
            throw new RequestIdMismatchException("expected request id {$request->getRequestId()}, got {$result['id']}");
        }
        if (isset($result['error'])) {
        }
        try {
            $request->getInvokingMethod()->setResult($this->buildResult($request->getInvokingMethod(), $result['result'] ?? []));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcResponse($request, $response);
    }

    /**
     * @param mixed $result
     */
    protected function buildResult(InvokingMethod $method, $result): array
    {
        return $result;
    }
}
