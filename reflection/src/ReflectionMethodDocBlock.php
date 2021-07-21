<?php

declare(strict_types=1);

namespace kuiper\reflection;

class ReflectionMethodDocBlock implements ReflectionMethodDocBlockInterface
{
    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @var ReflectionTypeInterface[]
     */
    private $parameterTypes;

    /**
     * @var ReflectionTypeInterface
     */
    private $returnType;

    /**
     * ReflectionMethodDocBlock constructor.
     *
     * @param ReflectionTypeInterface[] $parameterTypes
     */
    public function __construct(\ReflectionMethod $method, array $parameterTypes, ReflectionTypeInterface $returnType)
    {
        $this->method = $method;
        $this->parameterTypes = $parameterTypes;
        $this->returnType = $returnType;
    }

    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterTypes(): array
    {
        return $this->parameterTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnType(): ReflectionTypeInterface
    {
        return $this->returnType;
    }
}
