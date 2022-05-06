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

namespace kuiper\rpc;

use InvalidArgumentException;

class RpcMethod implements RpcMethodInterface
{
    /**
     * 返回值，形式为 [$returnValue, ...$outParams].
     */
    private ?array $result = null;

    private readonly object|string $target;

    /**
     * InvokingMethod constructor.
     *
     * @param object|string|mixed $target
     */
    public function __construct(
        mixed $target,
        private readonly ServiceLocator $serviceLocator,
        private readonly string $methodName,
        private readonly array $arguments)
    {
        if (is_object($target) || is_string($target)) {
            $this->target = $target;
        } else {
            throw new InvalidArgumentException('expect target is an object or class name, got '.gettype($target));
        }
    }

    public function getTarget(): object|string
    {
        return $this->target;
    }

    public function getTargetClass(): string
    {
        return is_string($this->target) ? $this->target : get_class($this->target);
    }

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }

    public function getServiceName(): string
    {
        return $this->serviceLocator->getName();
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->serviceLocator->getVersion();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->serviceLocator->getNamespace();
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
     * {@inheritDoc}
     */
    public function withArguments(array $args)
    {
        return new self($this->target, $this->serviceLocator, $this->methodName, $args);
    }

    public function getResult(): array
    {
        return $this->result ?? [];
    }

    /**
     * {@inheritDoc}
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
