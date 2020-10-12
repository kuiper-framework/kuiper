<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use Psr\Http\Message\ResponseInterface;

class MethodMetadata
{
    /**
     * @var string
     */
    private $httpMethod;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ReflectionTypeInterface
     */
    private $returnType;

    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * MethodMetadata constructor.
     */
    public function __construct(\ReflectionMethod $method)
    {
        $this->method = $method;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(string $httpMethod): void
    {
        $this->httpMethod = $httpMethod;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getReturnType(): ReflectionTypeInterface
    {
        return $this->returnType;
    }

    public function setReturnType(ReflectionTypeInterface $returnType): void
    {
        $this->returnType = $returnType;
    }

    public function getMethodName(): string
    {
        return sprintf('%s::%s', $this->method->getDeclaringClass()->getName(), $this->method->getName());
    }

    public function hasReturnType(): bool
    {
        return !($this->returnType instanceof VoidType)
            && !($this->returnType->isClass() && ResponseInterface::class === $this->returnType->getName());
    }
}
