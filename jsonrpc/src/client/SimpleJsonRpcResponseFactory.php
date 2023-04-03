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
        Assert::isInstanceOf($request, JsonRpcRequestInterface::class,
            'request should implements '.JsonRpcRequestInterface::class);
        $result = json_decode((string) $response->getBody(), true);
        if (false === $result
            || !isset($result['jsonrpc'])
            || !in_array($result['jsonrpc'], ['1.0', '2.0'], true)
            || !array_key_exists('id', $result)) {
            throw new BadResponseException($request, $response);
        }
        /** @var JsonRpcRequestInterface|RpcRequestInterface $request */
        if (null !== $result['id'] && $result['id'] !== $request->getRequestId()) {
            throw new RequestIdMismatchException("expected request id {$request->getRequestId()}, got {$result['id']}");
        }
        if (isset($result['error'])) {
            if (!isset($result['error']['code'], $result['error']['message'])) {
                throw new BadResponseException($request, $response);
            }
            // todo: 错误处理
            return $this->handleError($request, (int) $result['error']['code'], (string) $result['error']['message'], $result['error']['data'] ?? null);
        }
        try {
            $method = $request->getRpcMethod()->withResult($this->buildResult($request->getRpcMethod(), $result['result'] ?? []));
        } catch (InvalidArgumentException $e) {
            throw new BadResponseException($request, $response);
        }

        return new RpcResponse($request->withRpcMethod($method), $response);
    }

    /**
     * @param mixed $result
     */
    protected function buildResult(RpcMethodInterface $method, $result): array
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
