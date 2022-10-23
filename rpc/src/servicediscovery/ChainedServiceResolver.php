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

class ChainedServiceResolver implements ServiceResolverInterface
{
    /**
     * ChainRouteResolver constructor.
     *
     * @param ServiceResolverInterface[] $resolvers
     */
    public function __construct(private readonly array $resolvers)
    {
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
