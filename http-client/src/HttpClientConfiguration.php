<?php

declare(strict_types=1);

namespace kuiper\http\client;

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
     * @Inject({"options": "application.http-client"})
     */
    public function httpClient(HttpClientFactoryInterface $httpClientFactory, ?array $options): ClientInterface
    {
        return $httpClientFactory->create($options ?? []);
    }

    private function createHttpClientProxy(): array
    {
        $definitions = [];
        foreach (ComponentCollection::getAnnotations(HttpClient::class) as $annotation) {
            /** @var HttpClient $annotation */
            $definitions[$annotation->getComponentId()] = factory(function (ContainerInterface $container) use ($annotation) {
                if ($annotation->client || $annotation->responseParser) {
                    $factory = new HttpProxyClientFactory(
                        null !== $annotation->client ? $container->get($annotation->client) : $container->get(ClientInterface::class),
                        $container->get(AnnotationReaderInterface::class),
                        $container->get(NormalizerInterface::class)
                    );
                } else {
                    $factory = $container->get(HttpProxyClientFactory::class);
                }

                return $factory->create($annotation->getTargetClass());
            });
        }

        return $definitions;
    }
}
