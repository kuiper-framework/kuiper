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

namespace kuiper\rpc\client;

use kuiper\reflection\exception\ClassNotFoundException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\rpc\RpcMethodInterface;
use kuiper\serializer\NormalizerInterface;
use ReflectionException;
use ReflectionMethod;
use Webmozart\Assert\Assert;

class RpcResponseNormalizer
{
    /**
     * @var array
     */
    private array $cachedTypes;

    /**
     * JsonRpcSerializerResponseFactory constructor.
     */
    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
    ) {
    }

    /**
     * @param RpcMethodInterface $method
     * @param array              $result
     *
     * @return array
     *
     * @throws ReflectionException
     * @throws ClassNotFoundException
     */
    public function normalize(RpcMethodInterface $method, array $result): array
    {
        [$returnType, $outParamTypes] = $this->getMethodReturnTypes($method);
        if (empty($outParamTypes)) {
            if (isset($returnType)) {
                return [$this->normalizer->denormalize($result[0], $returnType)];
            }

            return [null];
        }
        $ret = [];
        Assert::count($result, count($outParamTypes) + 1, 'JsonRPC result value not match');
        if (isset($result[''])) {
            if (null !== $returnType) {
                $ret[] = $this->normalizer->denormalize($result[''], $returnType);
            } else {
                $ret[] = null;
            }
            foreach ($outParamTypes as $paramName => $type) {
                $ret[] = $this->normalizer->denormalize($result[$paramName] ?? null, $type);
            }
        } else {
            if (null !== $returnType) {
                $ret[] = $this->normalizer->denormalize($result[0], $returnType);
            } else {
                $ret[] = null;
            }
            foreach (array_values($outParamTypes) as $i => $type) {
                if (isset($result[$i + 1])) {
                    $ret[] = $this->normalizer->denormalize($result[$i + 1], $type);
                } else {
                    $ret[] = null;
                }
            }
        }

        return $ret;
    }

    /**
     * @throws ReflectionException
     * @throws ClassNotFoundException
     */
    private function getMethodReturnTypes(RpcMethodInterface $method): array
    {
        $key = $method->getTargetClass().'::'.$method->getMethodName();
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }
        $reflectionMethod = new ReflectionMethod($method->getTarget(), $method->getMethodName());
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

    /**
     * @param \ReflectionType|null    $phpType
     * @param ReflectionTypeInterface $docType
     *
     * @return ReflectionTypeInterface|null
     */
    private function createType(?\ReflectionType $phpType, ReflectionTypeInterface $docType): ?ReflectionTypeInterface
    {
        if (null === $phpType) {
            $type = $docType;
        } else {
            $type = ReflectionType::fromPhpType($phpType);
            if ($type->isUnknown()) {
                $type = $docType;
            }
        }

        return $type instanceof VoidType ? null : $type;
    }
}
