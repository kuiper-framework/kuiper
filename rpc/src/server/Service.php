<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\swoole\ServerPort;

class Service
{
    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $version;

    /**
     * @var object
     */
    private $service;

    /**
     * @var string[]
     */
    private $methods;

    /**
     * @var ServerPort
     */
    private $serverPort;

    /**
     * @var int
     */
    private $weight;

    /**
     * ServiceObject constructor.
     *
     * @param string   $serviceName
     * @param string   $version
     * @param object   $service
     * @param string[] $methods
     */
    public function __construct(string $serviceName, string $version, object $service, array $methods, ServerPort $serverPort, int $weight)
    {
        $this->serviceName = $serviceName;
        $this->version = $version;
        $this->service = $service;
        $this->methods = $methods;
        $this->serverPort = $serverPort;
        $this->weight = $weight;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return object
     */
    public function getService(): object
    {
        return $this->service;
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function hasMethod(string $method): bool
    {
        return in_array($method, $this->methods, true);
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
