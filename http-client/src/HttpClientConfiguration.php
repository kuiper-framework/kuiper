<?php

declare(strict_types=1);

namespace kuiper\http\client;

use Co\Client;
use DI\Annotation\Inject;
use function DI\autowire;
use function DI\factory;
use GuzzleHttp\ClientInterface;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\http\client\annotation\HttpClient;
use kuiper\serializer\NormalizerInterface;
use Psr\Container\ContainerInterface;

/**
 * @Configuration()
 * @ConditionalOnClass(ClientInterface::class)
 */
class HttpClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return array_merge($this->createHttpClientProxy(), [
            HttpClientFactoryInterface::class => autowire(HttpClientFactory::class),
        ]);
    }

    /**
     * @Bean()
     * @Inject({"options": "application.http_client.default"})
     */
    public function httpClient(ContainerInterface $container, HttpClientFactoryInterface $httpClientFactory, ?array $options): ClientInterface
    {
        if (isset($options['middleware'])) {
            foreach ($options['middleware'] as $i => $middleware) {
                if (is_string($middleware)) {
                    $options[$i] = $container->get($middleware);
                }
            }
        }

        return $httpClientFactory->create($options ?? []);
    }

    private function createHttpClientProxy(): array
    {
        $definitions = [];
        foreach (ComponentCollection::getAnnotations(HttpClient::class) as $annotation) {
            /** @var HttpClient $annotation */
            $definitions[$annotation->getComponentId()] = factory(function (ContainerInterface $container) use ($annotation) {
                $options = $container->get('application.http_client');
                $componentId = $annotation->getComponentId();
                if (isset($options[$componentId])) {
                    $httpClient = $container->get(HttpClientFactoryInterface::class)
                        ->create(array_merge($options[$componentId] ?? [], $options['default'] ?? []));
                } else {
                    $httpClient = $container->get(ClientInterface::class);
                }
                $factory = new HttpProxyClientFactory(
                    $httpClient,
                    $container->get(AnnotationReaderInterface::class),
                    $container->get(NormalizerInterface::class)
                );

                if ($annotation->responseParser) {
                    $factory->setRpcResponseFactory($container->get($annotation->responseParser));
                }

                return $factory->create($annotation->getTargetClass());
            });
        }

        return $definitions;
    }
}
