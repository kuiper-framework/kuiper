<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Exception\RequestException;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\VoidType;
use kuiper\serializer\NormalizerInterface;
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
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * MethodMetadata constructor.
     */
    public function __construct(\ReflectionMethod $method, NormalizerInterface $normalizer)
    {
        $this->method = $method;
        $this->normalizer = $normalizer;
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

    public function deserialize(ResponseInterface $response)
    {
        if ($this->willNotDeserialize()) {
            return $response;
        }
        $contentType = $response->getHeaderLine('content-type');
        if (false !== stripos($contentType, 'application/json')) {
            return $this->normalizer->denormalize(json_decode($response->getBody(), true), $this->returnType);
        } else {
            throw new \InvalidArgumentException("{$this->getMethodName()} should not declare return type");
        }
    }

    private function getMethodName(): string
    {
        return sprintf('%s::%s', $this->method->getDeclaringClass()->getName(), $this->method->getName());
    }

    public function handleError(RequestException $e)
    {
        throw $e;
    }

    private function willNotDeserialize(): bool
    {
        return $this->returnType->isUnknown()
            || $this->returnType instanceof VoidType
            || ($this->returnType->isClass() && ResponseInterface::class === $this->returnType->getName());
    }
}
