<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\transporter\ServiceEndpoint;
use kuiper\rpc\transporter\ServiceResolverInterface;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\integration\QueryFServant;

class TarsRegistryServiceResolver implements ServiceResolverInterface
{
    /**
     * @var QueryFServant
     */
    private $queryFClient;

    /**
     * RegistryConnectionFactory constructor.
     */
    public function __construct(QueryFServant $queryFClient)
    {
        $this->queryFClient = $queryFClient;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $service): ?ServiceEndpoint
    {
        $endpointFLists = $this->queryFClient->findObjectById($service);
        if (empty($endpointFLists)) {
            return null;
        }
        $endpoints = [];
        $weights = [];
        foreach ($endpointFLists  as $endpointF) {
            $endpoint = EndpointParser::fromEndpointF($endpointF);
            $endpoints[] = $endpoint;
            $weights[] = (int) ($endpoint->getOption('weight') ?? 100);
        }

        return new ServiceEndpoint($service, $endpoints, $weights);
    }
}
