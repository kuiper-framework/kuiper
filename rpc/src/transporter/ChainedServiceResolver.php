<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

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
    public function resolve(string $service): ?ServiceEndpoint
    {
        $serviceEndpoint = null;
        foreach ($this->resolvers as $resolver) {
            $serviceEndpoint = $resolver->resolve($service);
            if (null !== $serviceEndpoint) {
                break;
            }
        }

        return $serviceEndpoint;
    }
}
