<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

class ServiceEndpoint
{
    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var array
     */
    private $endpoints;

    /**
     * @var int[]
     */
    private $weights;

    /**
     * ServiceEndpoint constructor.
     *
     * @param string $serviceName
     * @param array  $endpoints
     * @param int[]  $weights
     */
    public function __construct(string $serviceName, array $endpoints, array $weights)
    {
        $this->serviceName = $serviceName;
        $this->endpoints = $endpoints;
        $this->weights = $weights;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return array
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }
}
