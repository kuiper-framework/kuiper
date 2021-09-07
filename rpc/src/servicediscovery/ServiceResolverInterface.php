<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\ServiceLocator;

interface ServiceResolverInterface
{
    /**
     * @param string $service
     *
     * @return ServiceEndpoint|null
     */
    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint;
}
