<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use kuiper\helper\Text;
use kuiper\jsonrpc\client\JsonRpcRequest;
use kuiper\jsonrpc\exception\ErrorCode;
use kuiper\jsonrpc\exception\JsonRpcRequestException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\server\ServerRequestFactoryInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Http\Message\RequestInterface;

class JsonRpcServerRequestFactory implements ServerRequestFactoryInterface
{
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
    public function __construct(NormalizerInterface $normalizer, ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
        $this->normalizer = $normalizer;
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    public function createRequest(RequestInterface $request): \kuiper\rpc\RequestInterface
    {
        $requestData = json_decode((string) $request->getBody(), true);
        if (false === $requestData) {
            throw new JsonRpcRequestException(null, 'Malformed json: '.json_last_error_msg(), ErrorCode::ERROR_PARSE);
        }
        if (!isset($requestData['jsonrpc'])) {
            throw new JsonRpcRequestException(null, 'Json RPC version not found', ErrorCode::ERROR_INVALID_REQUEST);
        }
        if (JsonRpcRequest::JSONRPC_VERSION !== $requestData['jsonrpc']) {
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
            [$className, $method] = $this->resolveMethod($method);
        } catch (\InvalidArgumentException $e) {
            throw new JsonRpcRequestException($id, "Json RPC method '{$method}' is invalid", ErrorCode::ERROR_INVALID_METHOD);
        }
        try {
            $method = new InvokingMethod($className, $method, $this->resolveParams($className, $method, $params));
        } catch (\ReflectionException $e) {
            throw new JsonRpcRequestException($id, "Json RPC method '{$method}' not found", ErrorCode::ERROR_INVALID_METHOD);
        }

        return new JsonRpcRequest($id, $request, $method);
    }

    private function resolveMethod(string $method): array
    {
        $pos = strrpos($method, '.');
        if (false === $pos) {
            throw new \InvalidArgumentException('invalid method');
        }

        return [
            str_replace('.', '\\', substr($method, 0, $pos)),
            substr($method, $pos + 1),
        ];
    }

    private function resolveParams(string $className, string $methodName, array $params): array
    {
        $paramTypes = $this->getParameterTypes($className, $methodName);
        $ret = [];
        foreach ($paramTypes as $i => $type) {
            $ret[] = $this->normalizer->denormalize($params[$i], $type);
        }

        return $ret;
    }

    private function getParameterTypes(string $className, string $methodName): array
    {
        $key = $className.'::'.$methodName;
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }
        $reflectionMethod = new \ReflectionMethod($className, $methodName);
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
