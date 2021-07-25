<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

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
    public function resolve(string $service): ?ServiceEndpoint
    {
        $endpoint = $this->cache->get($service, false);
        if (false === $endpoint) {
            $endpoint = $this->resolver->resolve($service);
            $this->cache->set($service, $endpoint);
        }

        return $endpoint;
    }
}
