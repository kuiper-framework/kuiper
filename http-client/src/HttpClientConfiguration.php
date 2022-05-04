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
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\Configuration;
use function DI\autowire;
use function DI\factory;
use GuzzleHttp\ClientInterface;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Text;
use kuiper\http\client\attribute\HttpClient;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use Psr\Container\ContainerInterface;

#[Configuration]
#[ConditionalOnClass(ClientInterface::class)]
class HttpClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return array_merge($this->createHttpClientProxy(), [
            HttpClientFactoryInterface::class => autowire(HttpClientFactory::class),
        ]);
    }

    #[Bean]
    public function httpClient(
        ContainerInterface $container,
        HttpClientFactoryInterface $httpClientFactory,
        #[Inject("application.http_client.default")] ?array $options): ClientInterface
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
        $self = $this;
        $definitions = [];
        foreach (ComponentCollection::getComponents(HttpClient::class) as $attribute) {
            /** @var HttpClient $annotation */
            $definitions[$annotation->getComponentId()] = factory(function (ContainerInterface $container) use ($self, $annotation) {
                $options = $container->get('application.http_client');
                /** @noinspection AmbiguousMethodsCallsInArrayMappingInspection */
                $componentId = $annotation->getComponentId();
                if (isset($options[$componentId])) {
                    $httpClient = $self->httpClient(
                        $container,
                        $container->get(HttpClientFactoryInterface::class),
                        array_merge($options['default'] ?? [], $options[$componentId] ?? [])
                    );
                } else {
                    $httpClient = $container->get(ClientInterface::class);
                }
                $factory = new HttpProxyClientFactory(
                    $httpClient,
                    $container->get(NormalizerInterface::class)
                );

                if (Text::isNotEmpty($annotation->responseParser)) {
                    $factory->setRpcResponseFactory($container->get($annotation->responseParser));
                }

                /** @phpstan-ignore-next-line */
                return $factory->create($annotation->getTargetClass());
            });
        }

        if (class_exists(Application::class)) {
            $httpClients = Application::getInstance()->getConfig()->get('application.http_client', []);
            $defaultOptions = $httpClients['default'] ?? [];
            foreach ($httpClients as $name => $options) {
                if ('default' === $name || isset($definitions[$name])) {
                    continue;
                }
                $options = array_merge($defaultOptions, $options);
                $definitions[$name] = factory(function (ContainerInterface $container) use ($self, $options): ClientInterface {
                    return $self->httpClient($container, $container->get(HttpClientFactoryInterface::class), $options);
                });
            }
        }

        return $definitions;
    }
}
