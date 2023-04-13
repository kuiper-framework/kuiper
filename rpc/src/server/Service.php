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

namespace kuiper\rpc\server;

use InvalidArgumentException;
use kuiper\rpc\ServiceLocator;
use kuiper\swoole\ServerPort;
use ReflectionMethod;

class Service
{
    /**
     * @param ServiceLocator                  $serviceLocator
     * @param object                          $service
     * @param array<string, ReflectionMethod> $methods
     * @param ServerPort                      $serverPort
     * @param int                             $weight
     */
    public function __construct(
        private readonly ServiceLocator $serviceLocator,
        private readonly object $service,
        private readonly array $methods,
        private readonly ServerPort $serverPort,
        private readonly int $weight = 100)
    {
    }

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator
    {
        return $this->serviceLocator;
    }

    /**
     * @return string
     */
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

    /**
     * @return object
     */
    public function getService(): object
    {
        return $this->service;
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(): array
    {
        return array_values($this->methods);
    }

    public function hasMethod(string $method): bool
    {
        return isset($this->methods[$method]);
    }

    public function getMethod(string $methodName): ReflectionMethod
    {
        if (!$this->hasMethod($methodName)) {
            throw new InvalidArgumentException("method $methodName not found");
        }

        return $this->methods[$methodName];
    }

    /**
     * @return ServerPort
     */
    public function getServerPort(): ServerPort
    {
        return $this->serverPort;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }
}
