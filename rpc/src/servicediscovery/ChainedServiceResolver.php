<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\ServiceLocator;

class ChainedServiceResolver implements ServiceResolverInterface
{
    /**
     * @var ServiceResolverInterface[]
     */
    private $resolvers;

    /**
     * ChainRouteResolver constructor.
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        $serviceEndpoint = null;
        foreach ($this->resolvers as $resolver) {
            $serviceEndpoint = $resolver->resolve($serviceLocator);
            if (null !== $serviceEndpoint) {
                break;
            }
        }

        return $serviceEndpoint;
    }
}
