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

namespace kuiper\tars\core;

use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\tars\attribute\TarsParameter;
use kuiper\tars\attribute\TarsReturnType;
use kuiper\tars\attribute\TarsServant;
use kuiper\tars\exception\SyntaxErrorException;
use kuiper\tars\type\TypeParser;
use kuiper\tars\type\VoidType;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * 读取调用方法 rpc ServantName, 参数，返回值等信息.
 *
 * Class MethodMetadataFactory
 */
class TarsMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var TypeParser
     */
    private readonly TypeParser $typeParser;

    /**
     * @var TarsMethodInterface[]
     */
    private array $cache = [];

    public function __construct(private readonly array $options = [])
    {
        $this->typeParser = new TypeParser();
    }

    /**
     * {@inheritdoc}
     */
    public function create(object|string $service, string $method, array $args): RpcMethodInterface
    {
        $serviceName = $this->options['service'] ?? (is_string($service) ? $service : get_class($service));
        $key = $serviceName . '::' . $method;
        if (!isset($this->cache[$key])) {
            try {
                $this->cache[$key] = $this->extractMethod($service, $method);
            } catch (ReflectionException|SyntaxErrorException $e) {
                throw new InvalidMethodException('read method metadata failed: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->cache[$key]->withArguments($args);
    }

    /**
     * @param object|string $servant
     * @param string $method
     * @return TarsMethodInterface
     * @throws InvalidMethodException
     * @throws ReflectionException
     * @throws SyntaxErrorException
     */
    protected function extractMethod(object|string $servant, string $method): TarsMethodInterface
    {
        $reflectionClass = new ReflectionClass($servant);
        $servantAnnotation = $this->getTarsServantAnnotation($reflectionClass);
        [$parameters, $returnValue] = $this->getParameters($servant, $method);

        return new TarsMethod($servant, $this->options['service'] ?? $servantAnnotation->getService(), $method, [], $parameters, $returnValue);
    }

    /**
     * @param object|string $servant
     * @param string $method
     *
     * @return array
     *
     * @throws InvalidMethodException
     * @throws SyntaxErrorException
     * @throws ReflectionException
     */
    protected function getParameters(object|string $servant, string $method): array
    {
        $reflectionClass = new ReflectionClass($servant);
        if (!$reflectionClass->hasMethod($method)) {
            throw new InvalidMethodException(sprintf("%s does not contain method '$method'", $reflectionClass->getName()));
        }

        $reflectionMethod = $this->getMethod($reflectionClass, $method);
        $namespace = $reflectionMethod->getDeclaringClass()->getNamespaceName();
        $parameters = [];
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            $attributes = $parameter->getAttributes(TarsParameter::class);
            $paramAttribute = null;
            if (count($attributes) > 0) {
                /** @var TarsParameter $paramAttribute */
                $paramAttribute = $attributes[0]->newInstance();
            }
            if ($paramAttribute !== null) {
                $parameters[] = new Parameter(
                    $paramAttribute->getOrder() ?? $i + 1,
                    $parameter->getName(),
                    $parameter->isPassedByReference(),
                    $this->typeParser->parse($paramAttribute->getType(), $namespace),
                    null
                );
            } else {
                $parameters[] = new Parameter(
                    $i + 1,
                    $parameter->getName(),
                    $parameter->isPassedByReference(),
                    $this->typeParser->fromPhpType($parameter->getType()),
                    null
                );
            }
        }

        $returnType = null;
        $attributes = $reflectionMethod->getAttributes(TarsReturnType::class);
        if (count($attributes) > 0)  {
            /** @var TarsReturnType $attribute */
            $attribute = $attributes[0]->newInstance();
            $returnType = $this->typeParser->parse($attribute->getName(), $namespace);
        } elseif ($reflectionMethod->getReturnType() instanceof ReflectionNamedType) {
            $returnType = $this->typeParser->fromPhpType($reflectionMethod->getReturnType());
        }
        $returnValue = Parameter::asReturnValue($returnType ?? VoidType::instance());

        return [$parameters, $returnValue];
    }

    /**
     * @throws ReflectionException
     * @throws InvalidMethodException
     */
    protected function getTarsServantAnnotation(ReflectionClass $reflectionClass): TarsServant
    {
        $attributes = $reflectionClass->getAttributes(TarsServant::class);
        if (count($attributes) === 0) {
            $interfaceName = ProxyGenerator::getInterfaceName($reflectionClass->getName());
            if (null !== $interfaceName) {
                $attributes = (new ReflectionClass($interfaceName))->getAttributes(TarsServant::class);
            } else {
                foreach ($reflectionClass->getInterfaceNames() as $servantInterface) {
                    $attributes = (new ReflectionClass($servantInterface))->getAttributes(TarsServant::class);
                    if (count($attributes) > 0) {
                        break;
                    }
                }
            }
        }
        if (count($attributes) > 0) {
            return $attributes[0]->newInstance();
        }

        throw new InvalidMethodException(sprintf('%s does not contain valid method definition, ' . "check it's interfaces should annotated with @TarsServant", $reflectionClass->getName()));
    }

    /**
     * @throws ReflectionException
     * @throws InvalidMethodException
     */
    protected function getMethod(ReflectionClass $reflectionClass, string $method): ReflectionMethod
    {
        $reflectionMethod = null;
        if ($reflectionClass->isInterface()) {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } else {
            $proxyClass = ProxyGenerator::getInterfaceName($reflectionClass->getName());
            if (null !== $proxyClass) {
                $reflectionMethod = new ReflectionMethod($proxyClass, $method);
            } else {
                foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
                    $attributes = (new ReflectionClass($interfaceName))->getAttributes(TarsServant::class, \ReflectionAttribute::IS_INSTANCEOF);
                    if (count($attributes) > 0) {
                        $reflectionMethod = new ReflectionMethod($interfaceName, $method);
                        break;
                    }
                }
            }
        }
        if (null === $reflectionMethod) {
            throw new InvalidMethodException("Cannot find method {$reflectionClass->getName()}::$method");
        }

        return $reflectionMethod;
    }
}
