<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\integration\QueryFServant;

class TarsRegistryResolver implements ServiceResolverInterface
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
    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        $endpointFLists = $this->queryFClient->findObjectById($serviceLocator->getName());
        if (empty($endpointFLists)) {
            return null;
        }
        $endpoints = [];
        foreach ($endpointFLists  as $endpointF) {
            $endpoint = EndpointParser::fromEndpointF($endpointF);
            $endpoints[] = $endpoint;
        }

        return new ServiceEndpoint($serviceLocator, $endpoints);
    }
}
