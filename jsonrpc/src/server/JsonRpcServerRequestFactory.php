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

namespace kuiper\jsonrpc\server;

use kuiper\helper\Text;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\ErrorCode;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class JsonRpcServerRequestFactory implements RpcServerRequestFactoryInterface
{
    public function __construct(private readonly RpcMethodFactoryInterface $rpcMethodFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(RequestInterface $request): RpcRequestInterface
    {
        $requestData = json_decode((string) $request->getBody(), true);
        if (false === $requestData) {
            throw new JsonRpcRequestException(null, 'Malformed json: '.json_last_error_msg(), ErrorCode::ERROR_PARSE);
        }
        if (!isset($requestData['jsonrpc'])) {
            throw new JsonRpcRequestException(null, 'Json RPC version not found', ErrorCode::ERROR_INVALID_REQUEST);
        }
        if (JsonRpcRequestInterface::JSONRPC_VERSION !== $requestData['jsonrpc']) {
            throw new JsonRpcRequestException(null, "Json RPC version {$requestData['jsonrpc']} is invalid", ErrorCode::ERROR_INVALID_REQUEST);
        }
        $id = $requestData['id'] ?? null;
        if (null === $id || !is_int($id)) {
            throw new JsonRpcRequestException(null, "Json RPC id '{$id}' is invalid", ErrorCode::ERROR_INVALID_REQUEST);
        }
        $method = $requestData['method'] ?? null;
        if (Text::isEmpty($method)) {
            throw new JsonRpcRequestException($id, "Json RPC method '{$method}' is invalid", ErrorCode::ERROR_INVALID_REQUEST);
        }
        $params = $requestData['params'] ?? null;
        if (!is_array($params)) {
            throw new JsonRpcRequestException($id, 'Json RPC params is invalid', ErrorCode::ERROR_INVALID_REQUEST);
        }

        return new JsonRpcServerRequest($request, $this->resolveMethod($id, $method, $params), $id, $requestData['jsonrpc']);
    }

    /**
     * @param int    $id
     * @param string $method
     * @param array  $params
     *
     * @return RpcMethodInterface
     *
     * @throws JsonRpcRequestException
     */
    private function resolveMethod(int $id, string $method, array $params): RpcMethodInterface
    {
        $pos = strrpos($method, '.');
        if (false === $pos) {
            throw new JsonRpcRequestException($id, "JsonRPC method '{$method}' is invalid", ErrorCode::ERROR_INVALID_METHOD);
        }

        $serviceName = substr($method, 0, $pos);
        $methodName = substr($method, $pos + 1);
        try {
            return $this->rpcMethodFactory->create($serviceName, $methodName, $params);
        } catch (InvalidMethodException $e) {
            throw new JsonRpcRequestException($id, "JsonRPC method '{$method}' not found", ErrorCode::ERROR_INVALID_METHOD);
        }
    }
}
