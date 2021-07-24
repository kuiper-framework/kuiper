<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\helper\Text;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\jsonrpc\exception\ErrorCode;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Http\Message\RequestInterface;

class JsonRpcServerRequestFactory implements RpcServerRequestFactoryInterface
{
    /**
     * @var array
     */
    private $services;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ReflectionDocBlockFactoryInterface
     */
    private $reflectionDocBlockFactory;

    /**
     * @var array
     */
    private $cachedTypes;

    /**
     * JsonRpcSerializerResponseFactory constructor.
     */
    public function __construct(array $services, NormalizerInterface $normalizer, ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
        $this->services = $services;
        $this->normalizer = $normalizer;
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

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
        try {
            [$target, $method] = $this->resolveMethod($id, $method);
        } catch (\InvalidArgumentException $e) {
            throw new JsonRpcRequestException($id, "JsonRPC method '{$method}' is invalid", ErrorCode::ERROR_INVALID_METHOD);
        }
        try {
            $method = new InvokingMethod($target, $method, $this->resolveParams($target, $method, $params));
        } catch (\ReflectionException $e) {
            throw new JsonRpcRequestException($id, "JsonRPC method '{$method}' not found", ErrorCode::ERROR_INVALID_METHOD);
        }

        return new JsonRpcServerRequest($request, $method, $id);
    }

    private function resolveMethod(int $id, string $method): array
    {
        $pos = strrpos($method, '.');
        if (false === $pos) {
            throw new \InvalidArgumentException('invalid method');
        }

        $serviceName = substr($method, 0, $pos);
        $methodName = substr($method, $pos + 1);
        if (!isset($this->services[$serviceName])) {
            $serviceName = str_replace('.', '\\', $serviceName);
        }
        if (isset($this->services[$serviceName])) {
            return [$this->services[$serviceName], $methodName];
        }
        throw new JsonRpcRequestException($id, "JsonRPC method '{$method}' not found", ErrorCode::ERROR_INVALID_METHOD);
    }

    private function resolveParams(object $target, string $methodName, array $params): array
    {
        $paramTypes = $this->getParameterTypes($target, $methodName);
        $ret = [];
        foreach ($paramTypes as $i => $type) {
            $ret[] = $this->normalizer->denormalize($params[$i], $type);
        }

        return $ret;
    }

    private function getParameterTypes(object $target, string $methodName): array
    {
        $key = get_class($target).'::'.$methodName;
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }

        $reflectionMethod = new \ReflectionMethod($target, $methodName);
        $docParamTypes = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod)->getParameterTypes();
        $paramTypes = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $paramTypes[] = $this->createType($parameter->getType(), $docParamTypes[$parameter->getName()]);
        }

        return $this->cachedTypes[$key] = $paramTypes;
    }

    private function createType(?\ReflectionType $type, ReflectionTypeInterface $docType): ?ReflectionTypeInterface
    {
        if (null === $type && $docType instanceof VoidType) {
            return null;
        }
        if (null === $type) {
            return $docType;
        }
        $reflectionType = ReflectionType::fromPhpType($type);
        if ($reflectionType->isUnknown()) {
            return $docType;
        }

        return $reflectionType;
    }
}
