<?php

declare(strict_types=1);

namespace kuiper\rpc;

class InvokingMethod
{
    /**
     * @var object|string
     */
    private $target;

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
    public function __construct($target, string $methodName, array $arguments)
    {
        if (is_object($target) || is_string($target)) {
            $this->target = $target;
        } else {
            throw new \InvalidArgumentException('expect target is an object or class name, got '.gettype($target));
        }
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
        return is_object($this->target) ? get_class($this->target) : $this->target;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    public function getFullMethodName(): string
    {
        return $this->getTargetClass().'::'.$this->methodName;
    }
}
