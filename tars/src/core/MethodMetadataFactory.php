<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\tars\annotation\TarsParameter;
use kuiper\tars\annotation\TarsReturnType;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\type\TypeParser;

/**
 * 读取调用方法 rpc ServantName, 参数，返回值等信息.
 *
 * Class MethodMetadataFactory
 */
class MethodMetadataFactory implements MethodMetadataFactoryInterface
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
     * @var MethodMetadata[]
     */
    private $cache;

    public function __construct(AnnotationReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->typeParser = new TypeParser($annotationReader);
    }

    /**
     * {@inheritdoc}
     */
    public function create($servant, string $method): MethodMetadata
    {
        $key = (is_string($servant) ? $servant : get_class($servant)).'::'.$method;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        try {
            return $this->cache[$key] = $this->getMetadataFromAnnotation($servant, $method);
        } catch (\ReflectionException $e) {
            throw new InvalidMethodException('read method metadata failed', $e->getCode(), $e);
        }
    }

    /**
     * @param object|string $servant
     *
     * @throws \ReflectionException
     */
    private function getMetadataFromAnnotation($servant, string $method): MethodMetadata
    {
        $reflectionClass = new \ReflectionClass($servant);
        if (!$reflectionClass->hasMethod($method)) {
            throw new InvalidMethodException(sprintf("%s does not contain method '$method'", $reflectionClass));
        }
        $servantAnnotation = $this->getTarsServantAnnotation($reflectionClass);

        if ($reflectionClass->isInterface()) {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } else {
            $reflectionMethod = new \ReflectionMethod(ProxyGenerator::getInterfaceName($reflectionClass->getName()), $method);
        }
        $namespace = $reflectionClass->getNamespaceName();
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

        return new MethodMetadata(
            $reflectionClass->getName(),
            $reflectionClass->getNamespaceName(),
            $method,
            $servantAnnotation->value,
            $parameters,
            $returnType
        );
    }

    private function getTarsServantAnnotation(\ReflectionClass $reflectionClass): TarsServant
    {
        /** @var TarsServant|null $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, TarsServant::class);
        if (null === $annotation) {
            $interfaceName = ProxyGenerator::getInterfaceName($reflectionClass->getName());
            if (null !== $interfaceName) {
                $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($interfaceName), TarsServant::class);
            }
        }
        if (null !== $annotation) {
            return $annotation;
        }

        throw new InvalidMethodException(sprintf('%s does not contain valid method definition, '."check it's interfaces should annotated with @TarsServant", $reflectionClass->getName()));
    }
}
