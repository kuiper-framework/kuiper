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

namespace kuiper\http\client;

use DI\Attribute\Inject;

use function DI\autowire;
use function DI\factory;

use GuzzleHttp\ClientInterface;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\http\client\attribute\HttpClient;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use Psr\Container\ContainerInterface;

#[Configuration, BootstrapConfiguration]
#[ConditionalOnClass(ClientInterface::class)]
class HttpClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        if (class_exists(Application::class) && Application::hasInstance()) {
            Application::getInstance()->getConfig()->mergeIfNotExists([
                'application' => [
                    'http_client' => [
                        'default' => [
                            'logging' => 'true' === env('HTTP_CLIENT_LOGGING'),
                            'log_format' => env('HTTP_CLIENT_LOG_FORMAT'),
                            'retry' => (int) env('HTTP_CLIENT_RETRY', '0'),
                        ],
                    ],
                ],
            ]);
        }

        return array_merge($this->createHttpClientProxy(), [
            HttpClientFactoryInterface::class => autowire(HttpClientFactory::class),
        ]);
    }

    #[Bean]
    public function httpClient(
        ContainerInterface $container,
        HttpClientFactoryInterface $httpClientFactory,
        #[Inject('application.http_client.default')] ?array $options
    ): ClientInterface {
        if (isset($options['middleware'])) {
            foreach ($options['middleware'] as $i => $middleware) {
                if (is_string($middleware)) {
                    $options['middleware'][$i] = $container->get($middleware);
                }
            }
        }

        return $httpClientFactory->create($options ?? []);
    }

    private function envOptions(string $componentId): array
    {
        $prefix = 'HTTP_CLIENT_'.str_replace(['.', '\\'], '_', strtoupper($componentId)).'__';
        $options = [];
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $name = strtolower(substr($key, strlen($prefix)));
                $options[$name] = $value;
            }
        }

        return $options;
    }

    private function createHttpClientProxy(): array
    {
        $self = $this;
        $definitions = [];
        foreach (ComponentCollection::getComponents(HttpClient::class) as $attribute) {
            /** @var HttpClient $attribute */
            $definitions[$attribute->getComponentId()] = factory(function (ContainerInterface $container) use ($self, $attribute) {
                $options = $container->get('application.http_client');
                /** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
                $componentId = $attribute->getComponentId();
                $clientOptions = array_merge(
                    $options['default'] ?? [],
                    $this->envOptions($componentId),
                    $options[$componentId] ?? []
                );
                $httpClient = $self->httpClient(
                    $container,
                    $container->get(HttpClientFactoryInterface::class),
                    $clientOptions
                );
                $factory = new HttpProxyClientFactory(
                    $httpClient,
                    $container->get(NormalizerInterface::class)
                );

                if ('' !== $attribute->getResponseParser()) {
                    $factory->setRpcResponseFactory($container->get($attribute->getResponseParser()));
                }

                /** @phpstan-ignore-next-line */
                return $factory->create($attribute->getTargetClass());
            });
        }

        if (class_exists(Application::class)) {
            $httpClients = Application::getInstance()->getConfig()->get('application.http_client', []);
            $defaultOptions = $httpClients['default'] ?? [];
            foreach ($httpClients as $name => $options) {
                if ('default' === $name || isset($definitions[$name])) {
                    continue;
                }
                $options += $defaultOptions;
                $definitions[$name] = factory(function (ContainerInterface $container) use ($self, $options): ClientInterface {
                    return $self->httpClient($container, $container->get(HttpClientFactoryInterface::class), $options);
                });
            }
        }

        return $definitions;
    }
}
