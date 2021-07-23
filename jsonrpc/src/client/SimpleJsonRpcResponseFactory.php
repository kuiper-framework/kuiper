<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\RpcRpcResponse;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class SimpleJsonRpcResponseFactory implements RpcResponseFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        Assert::isInstanceOf($request, JsonRpcRequestInterface::class,
            'request should implements '.JsonRpcRequestInterface::class);
        $result = json_decode((string) $response->getBody(), true);
        if (false === $result
            || !isset($result['jsonrpc'])
            || JsonRpcRequestInterface::JSONRPC_VERSION !== $result['jsonrpc']
            || !array_key_exists('id', $result)) {
            throw new BadResponseException($request, $response);
        }
        /** @var JsonRpcRequestInterface $request */
        if (null !== $result['id'] && $result['id'] !== $request->getRequestId()) {
            throw new RequestIdMismatchException("expected request id {$request->getRequestId()}, got {$result['id']}");
        }
        if (isset($result['error'])) {
            // todo: 错误处理
        }
        try {
            $request->getInvokingMethod()->setResult($this->buildResult($request->getInvokingMethod(), $result['result'] ?? []));
        } catch (\InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcRpcResponse($request, $response);
    }

    /**
     * @param mixed $result
     */
    protected function buildResult(InvokingMethod $method, $result): array
    {
        return $result;
    }
}
