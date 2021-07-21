<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\rpc\InvokingMethod;
use kuiper\serializer\NormalizerInterface;
use Webmozart\Assert\Assert;

class JsonRpcResponseFactory extends SimpleJsonRpcResponseFactory
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

    protected function buildResult(InvokingMethod $method, $result): array
    {
        [$returnType, $outParamTypes] = $this->getMethodReturnTypes($method);
        if (empty($outParamTypes)) {
            if (isset($returnType)) {
                return [$this->normalizer->denormalize($result, $returnType)];
            }

            return [null];
        }
        $ret = [];
        Assert::isArray($result);
        Assert::count($result, count($outParamTypes) + (null !== $returnType ? 1 : 0));
        if (isset($result[''])) {
            foreach ($outParamTypes as $paramName => $type) {
                $ret[] = $this->normalizer->denormalize($result[$paramName] ?? null, $type);
            }
            if (null !== $returnType) {
                $ret[] = $this->normalizer->denormalize($result[''], $returnType);
            }
        } else {
            foreach (array_values($outParamTypes) as $i => $type) {
                $ret[] = $this->normalizer->denormalize($result[$i], $type);
            }
            if (null !== $returnType) {
                $ret[] = $this->normalizer->denormalize($result[count($outParamTypes)], $returnType);
            }
        }

        return $ret;
    }

    private function getMethodReturnTypes(InvokingMethod $method): array
    {
        $key = $method->getFullMethodName();
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }
        $reflectionMethod = new \ReflectionMethod($method->getTarget(), $method->getMethodName());
        $docBlock = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod);
        $returnType = $this->createType($reflectionMethod->getReturnType(), $docBlock->getReturnType());
        $docParamTypes = $docBlock->getParameterTypes();
        $types = [];
        foreach ($reflectionMethod->getParameters() as $parameter) {
            if ($parameter->isPassedByReference()) {
                $types[$parameter->getName()] = $this->createType($parameter->getType(), $docParamTypes[$parameter->getName()]);
            }
        }

        return $this->cachedTypes[$key] = [$returnType, $types];
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
