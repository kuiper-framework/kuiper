<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\ServiceLocator;
use Psr\SimpleCache\CacheInterface;

class CachedServiceResolver implements ServiceResolverInterface
{
    /**
     * @var ServiceResolverInterface
     */
    private $resolver;
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * ChainRouteResolver constructor.
     */
    public function __construct(ServiceResolverInterface $resolver, CacheInterface $cache)
    {
        $this->resolver = $resolver;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        $key = (string) $serviceLocator;
        $endpoint = $this->cache->get($key, false);
        if (false === $endpoint) {
            $endpoint = $this->resolver->resolve($serviceLocator);
            $this->cache->set($key, $endpoint);
        }

        return $endpoint;
    }
}
