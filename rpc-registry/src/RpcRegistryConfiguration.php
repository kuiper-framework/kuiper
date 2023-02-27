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

namespace kuiper\rpc\registry;

use DI\Attribute\Inject;

use function DI\autowire;
use function DI\factory;
use function DI\get;

use GuzzleHttp\ClientInterface;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\http\client\HttpProxyClientFactory;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\registry\consul\ConsulAgent;
use kuiper\rpc\registry\consul\ConsulServiceRegistry;
use kuiper\rpc\registry\consul\ConsulServiceResolver;
use kuiper\rpc\server\listener\ServiceDiscoveryListener;
use kuiper\rpc\server\ServiceRegistryInterface;
use kuiper\rpc\servicediscovery\InMemoryCache;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceAlgorithm;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;

class RpcRegistryConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            ConsulAgent::class => factory(function (ContainerInterface $container): ConsulAgent {
                $clientFactory = new HttpProxyClientFactory(
                    $container->get('consulHttpClient'),
                    $container->get(NormalizerInterface::class)
                );

                return $clientFactory->create(ConsulAgent::class);
            }),
            ServiceDiscoveryListener::class => autowire(ServiceDiscoveryListener::class)
                ->constructorParameter('services', get('registerServices')),
        ];
    }

    #[Bean]
    public function serviceDiscovery(
        ServiceResolverInterface $serviceResolver,
        #[Inject('application.client.service_discovery.load_balance')] ?string $lb): ServiceDiscovery
    {
        return new ServiceDiscovery(
            $serviceResolver,
            new InMemoryCache(),
            null === $lb ? LoadBalanceAlgorithm::ROUND_ROBIN : LoadBalanceAlgorithm::from($lb)
        );
    }

    #[Bean('consulHttpClient')]
    public function consulHttpClient(HttpClientFactoryInterface $httpClientFactory,
                                     #[Inject('application.consul')] ?array $options): ClientInterface
    {
        return $httpClientFactory->create(array_merge([
            'base_uri' => 'http://localhost:8500',
            'http_errors' => false,
        ], $options ?? []));
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnProperty('application.consul'),
        new ConditionalOnProperty('application.server.service_discovery.type', hasValue: 'consul', matchIfMissing: true)
    )]
    public function consulServerRegistry(ConsulAgent $consulAgent,
                                         #[Inject('application.server.service_discovery')] ?array $options): ServiceRegistryInterface
    {
        return new ConsulServiceRegistry($consulAgent, $options ?? []);
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnProperty('application.consul'),
        new ConditionalOnProperty('application.server.service_discovery.type', hasValue: 'consul', matchIfMissing: true)
    )]
    public function consulServerResolver(ConsulAgent $consulAgent): ServiceResolverInterface
    {
        return new ConsulServiceResolver($consulAgent);
    }
}
