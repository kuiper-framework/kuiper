<?php

declare(strict_types=1);

namespace kuiper\http\client;

use DI\Annotation\Inject;
use function DI\autowire;
use GuzzleHttp\ClientInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

/**
 * @Configuration()
 * @ConditionalOnClass(ClientInterface::class)
 */
class HttpClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            HttpClientFactoryInterface::class => autowire(HttpClientFactory::class),
        ];
    }

    /**
     * @Bean()
     * @Inject({"options": "application.http-client"})
     */
    public function httpClient(HttpClientFactoryInterface $httpClientFactory, ?array $options): ClientInterface
    {
        return $httpClientFactory->create($options ?? []);
    }
}
