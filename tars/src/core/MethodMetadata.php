<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use kuiper\tars\type\Type;
use kuiper\tars\type\VoidType;

class MethodMetadata implements MethodMetadataInterface
{
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var string
     */
    private $className;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var string
     */
    private $servantName;
    /**
     * @var ParameterInterface[]
     */
    private $parameters;
    /**
     * @var ParameterInterface
     */
    private $returnType;

    /**
     * MethodMetadata constructor.
     */
    public function __construct(
        string $className,
        string $namespace,
        string $methodName,
        string $servantName,
        array $parameters,
        ?Type $returnType = null)
    {
        $this->className = $className;
        $this->namespace = $namespace;
        $this->methodName = $methodName;
        $this->servantName = $servantName;
        $this->parameters = $parameters;
        $this->returnType = Parameter::asReturnValue($returnType ?? VoidType::instance());
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getServantName(): string
    {
        return $this->servantName;
    }

    /**
     * @return ParameterInterface[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getReturnValue(): ParameterInterface
    {
        return $this->returnType;
    }
}
