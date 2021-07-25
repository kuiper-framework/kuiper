<?php

declare(strict_types=1);

namespace kuiper\rpc;

class RpcMethod implements RpcMethodInterface
{
    /**
     * @var object|string
     */
    private $target;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var array
     */
    private $arguments;

    /**
     * 返回值，形式为 [$returnValue, ...$outParams].
     *
     * @var array
     */
    private $result;

    /**
     * InvokingMethod constructor.
     *
     * @param object|string|mixed $target
     */
    public function __construct($target, ?string $serviceName, string $methodName, array $arguments)
    {
        if (is_object($target) || is_string($target)) {
            $this->target = $target;
        } else {
            throw new \InvalidArgumentException('expect target is an object or class name, got '.gettype($target));
        }
        $this->serviceName = $serviceName ?? $this->getTargetClass();
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * @return object|string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function getTargetClass(): string
    {
        return is_string($this->target) ? $this->target : get_class($this->target);
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function withArguments(array $args)
    {
        $copy = clone $this;
        $copy->arguments = $args;
        return $copy;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @inheritDoc
     */
    public function withResult(array $result)
    {
        $copy = clone $this;
        $copy->result = $result;
        return $copy;
    }

    public function __toString(): string
    {
        return $this->getServiceName().'::'.$this->methodName;
    }
}
