<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\tars\annotation\TarsParameter;
use kuiper\tars\annotation\TarsReturnType;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\exception\SyntaxErrorException;
use kuiper\tars\type\TypeParser;
use kuiper\tars\type\VoidType;
use ReflectionException;

/**
 * 读取调用方法 rpc ServantName, 参数，返回值等信息.
 *
 * Class MethodMetadataFactory
 */
class TarsMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var TypeParser
     */
    private $typeParser;

    /**
     * @var TarsMethodInterface[]
     */
    private $cache;

    public function __construct(?AnnotationReaderInterface $annotationReader = null)
    {
        $this->annotationReader = $annotationReader ?? AnnotationReader::getInstance();
        $this->typeParser = new TypeParser($this->annotationReader);
    }

    /**
     * {@inheritdoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $key = (is_string($service) ? $service : get_class($service)).'::'.$method;
        if (!isset($this->cache[$key])) {
            try {
                $this->cache[$key] = $this->extractMethod($service, $method);
            } catch (ReflectionException | SyntaxErrorException $e) {
                throw new InvalidMethodException('read method metadata failed: '.$e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->cache[$key]->withArguments($args);
    }

    /**
     * @param object|string $servant
     *
     * @throws ReflectionException
     * @throws SyntaxErrorException
     */
    protected function extractMethod($servant, string $method): TarsMethodInterface
    {
        $reflectionClass = new \ReflectionClass($servant);
        $servantAnnotation = $this->getTarsServantAnnotation($reflectionClass);
        [$parameters, $returnValue] = $this->getParameters($servant, $method);

        return new TarsMethod($servant, $servantAnnotation->service ?? '', $method, [], $parameters, $returnValue);
    }

    /**
     * @param object|string $servant
     * @param string        $method
     *
     * @return array
     *
     * @throws InvalidMethodException
     * @throws SyntaxErrorException
     */
    protected function getParameters($servant, string $method): array
    {
        $reflectionClass = new \ReflectionClass($servant);
        if (!$reflectionClass->hasMethod($method)) {
            throw new InvalidMethodException(sprintf("%s does not contain method '$method'", $reflectionClass->getName()));
        }

        $reflectionMethod = $this->getMethod($reflectionClass, $method);
        $namespace = $reflectionMethod->getDeclaringClass()->getNamespaceName();
        $parameters = [];
        $returnType = null;
        foreach ($this->annotationReader->getMethodAnnotations($reflectionMethod) as $methodAnnotation) {
            if ($methodAnnotation instanceof TarsParameter) {
                $parameters[] = new Parameter(
                    $methodAnnotation->order ?? count($parameters) + 1,
                    $methodAnnotation->name,
                    $methodAnnotation->out ?? false,
                    $this->typeParser->parse($methodAnnotation->type, $namespace),
                    null
                );
            } elseif ($methodAnnotation instanceof TarsReturnType) {
                $returnType = $this->typeParser->parse($methodAnnotation->value, $namespace);
            }
        }
        $returnValue = Parameter::asReturnValue($returnType ?? VoidType::instance());

        return [$parameters, $returnValue];
    }

    protected function getTarsServantAnnotation(\ReflectionClass $reflectionClass): TarsServant
    {
        /** @var TarsServant|null $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, TarsServant::class);
        if (null === $annotation) {
            $interfaceName = ProxyGenerator::getInterfaceName($reflectionClass->getName());
            if (null !== $interfaceName) {
                $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($interfaceName), TarsServant::class);
            } else {
                foreach ($reflectionClass->getInterfaceNames() as $servantInterface) {
                    $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($servantInterface), TarsServant::class);
                    if (null !== $annotation) {
                        break;
                    }
                }
            }
        }
        if (null !== $annotation) {
            return $annotation;
        }

        throw new InvalidMethodException(sprintf('%s does not contain valid method definition, '."check it's interfaces should annotated with @TarsServant", $reflectionClass->getName()));
    }

    protected function getMethod(\ReflectionClass $reflectionClass, string $method): \ReflectionMethod
    {
        $reflectionMethod = null;
        if ($reflectionClass->isInterface()) {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } else {
            $proxyClass = ProxyGenerator::getInterfaceName($reflectionClass->getName());
            if (null !== $proxyClass) {
                $reflectionMethod = new \ReflectionMethod($proxyClass, $method);
            } else {
                foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
                    $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($interfaceName), TarsServant::class);
                    if (null !== $annotation) {
                        $reflectionMethod = new \ReflectionMethod($interfaceName, $method);
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
