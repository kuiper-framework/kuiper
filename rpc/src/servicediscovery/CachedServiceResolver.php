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

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\ServiceLocator;
use Psr\SimpleCache\CacheInterface;

class CachedServiceResolver implements ServiceResolverInterface
{
    /**
     * ChainRouteResolver constructor.
     */
    public function __construct(
        private readonly ServiceResolverInterface $resolver,
        private readonly CacheInterface $cache)
    {
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
