<?php

declare(strict_types=1);

namespace kuiper\rpc\registry;

use DI\Annotation\Inject;
use function DI\factory;
use GuzzleHttp\ClientInterface;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\AllConditions;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\http\client\HttpProxyClientFactory;
use kuiper\rpc\registry\consul\ConsulAgent;
use kuiper\rpc\registry\consul\ConsulServiceRegistry;
use kuiper\rpc\registry\consul\ConsulServiceResolver;
use kuiper\rpc\server\ServiceRegistryInterface;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;

class RpcRegistryConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            ConsulAgent::class => factory(function (ContainerInterface $container) {
                $clientFactory = new HttpProxyClientFactory(
                    $container->get('consulHttpClient'),
                    $container->get(AnnotationReaderInterface::class),
                    $container->get(NormalizerInterface::class)
                );

                return $clientFactory->create(ConsulAgent::class);
            }),
        ];
    }

    /**
     * @Bean("consulHttpClient")
     * @Inject({"options": "application.consul"})
     */
    public function consulHttpClient(HttpClientFactoryInterface $httpClientFactory, ?array $options): ClientInterface
    {
        return $httpClientFactory->create(array_merge([
            'base_uri' => 'http://localhost:8500',
        ], $options ?? []));
    }

    /**
     * @Bean()
     * @AllConditions({
     *     @ConditionalOnProperty("application.consul"),
     *     @ConditionalOnProperty("application.server.service_discovery", hasValue="consul", matchIfMissing=true)
     * })
     */
    public function consulServerRegistry(ConsulAgent $consulAgent): ServiceRegistryInterface
    {
        return new ConsulServiceRegistry($consulAgent);
    }

    /**
     * @Bean()
     * @AllConditions({
     *     @ConditionalOnProperty("application.consul"),
     *     @ConditionalOnProperty("application.client.service_discovery", hasValue="consul", matchIfMissing=true)
     * })
     */
    public function consulServerResolver(ConsulAgent $consulAgent): ServiceResolverInterface
    {
        return new ConsulServiceResolver($consulAgent);
    }
}
