<?php

declare(strict_types=1);

namespace kuiper\http\client;

use DI\Annotation\Inject;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\Configuration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\pool\PoolFactoryInterface;

/**
 * @Configuration()
 * @ConditionalOnClass(ClientInterface::class)
 */
class HttpClientConfiguration
{
    /**
     * @Bean()
     * @Inject({"options": "application.http-client"})
     */
    public function httpClient(HttpClientFactoryInterface $httpClientFactory, LoggerFactoryInterface $loggerFactory, ?array $options): ClientInterface
    {
        if (!isset($options['handler'])) {
            $options['handler'] = HandlerStack::create();
        }
        if (!empty($options['logging'])) {
            $logger = $loggerFactory->create(ClientInterface::class);
            $format = strtoupper($options['log-format'] ?? 'clf');
            if (defined(MessageFormatter::class.'::'.$format)) {
                $format = constant(MessageFormatter::class.'::'.$format);
            }
            $formatter = new MessageFormatter($format);
            $middleware = Middleware::log($logger, $formatter, strtolower($options['log-level'] ?? 'info'));
            $options['handler']->push($middleware);
        }

        return $httpClientFactory->create($options);
    }

    /**
     * @Bean()
     */
    public function httpClientFactory(PoolFactoryInterface $poolFactory): HttpClientFactoryInterface
    {
        return new HttpClientFactory($poolFactory);
    }
}
