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

namespace kuiper\jsonrpc\client;

use InvalidArgumentException;
use JsonException;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\exception\BadResponseException;
use kuiper\rpc\exception\RequestIdMismatchException;
use kuiper\rpc\exception\ServerException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class SimpleJsonRpcResponseFactory implements RpcResponseFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createResponse(RpcRequestInterface $request, ResponseInterface $response): RpcResponseInterface
    {
        Assert::isInstanceOf(
            $request,
            JsonRpcRequestInterface::class,
            'request should implements '.JsonRpcRequestInterface::class
        );
        try {
            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadResponseException('Json parse failed', $request, $response, $e);
        }
        if (!isset($result['jsonrpc'])) {
            throw new BadResponseException('jsonrpc version is missing', $request, $response);
        }
        if (!in_array($result['jsonrpc'], ['1.0', '2.0'], true)) {
            throw new BadResponseException('jsonrpc version not match, expected 2.0, got '.$response['jsonrpc'], $request, $response);
        }
        if (!array_key_exists('id', $result)) {
            throw new BadResponseException('jsonrpc request id is missing', $request, $response);
        }
        /** @var JsonRpcRequestInterface|RpcRequestInterface $request */
        if (null !== $result['id'] && $result['id'] !== $request->getRequestId()) {
            throw new RequestIdMismatchException("expected request id {$request->getRequestId()}, got {$result['id']}");
        }
        if (isset($result['error'])) {
            if (!isset($result['error']['code'], $result['error']['message'])) {
                throw new BadResponseException('jsonrpc error message is missing', $request, $response);
            }
            // todo: 错误处理
            return $this->handleError($request, (int) $result['error']['code'], (string) $result['error']['message'], $result['error']['data'] ?? null);
        }
        try {
            $method = $request->getRpcMethod()->withResult($this->buildResult($request->getRpcMethod(), $result['result'] ?? [], $result));
        } catch (InvalidArgumentException $e) {
            throw new BadResponseException('Parse result fail', $request, $response, $e);
        }

        return new RpcResponse($request->withRpcMethod($method), $response);
    }

    /**
     * @param mixed $result
     */
    protected function buildResult(RpcMethodInterface $method, $result, array $context): array
    {
        return $result;
    }

    /**
     * @param JsonRpcRequestInterface $request
     * @param int                     $code
     * @param string                  $message
     * @param mixed                   $data
     *
     * @return RpcResponseInterface
     *
     * @throws ServerException
     */
    protected function handleError(JsonRpcRequestInterface $request, int $code, string $message, $data): RpcResponseInterface
    {
        throw new ServerException($message, $code);
    }
}
