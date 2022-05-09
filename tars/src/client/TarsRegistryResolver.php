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

namespace kuiper\tars\client;

use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\integration\QueryFServant;

class TarsRegistryResolver implements ServiceResolverInterface
{
    /**
     * RegistryConnectionFactory constructor.
     */
    public function __construct(private readonly QueryFServant $queryFClient)
    {
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
