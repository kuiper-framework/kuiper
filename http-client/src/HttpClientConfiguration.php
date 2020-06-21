<?php

declare(strict_types=1);

namespace kuiper\http\client;

use DI\Annotation\Inject;
use GuzzleHttp\ClientInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use kuiper\swoole\pool\PoolFactoryInterface;

/**
 * @Configuration()
 */
class HttpClientConfiguration
{
    /**
     * @Bean()
     * @Inject({"httpClientConfig": "application.http-client"})
     */
    public function httpClient(HttpClientFactoryInterface $httpClientFactory, ?array $httpClientConfig): ClientInterface
    {
        return $httpClientFactory->create($httpClientConfig ?? []);
    }

    /**
     * @Bean()
     */
    public function httpClientFactory(PoolFactoryInterface $poolFactory): HttpClientFactoryInterface
    {
        return new HttpClientFactory($poolFactory);
    }
}
