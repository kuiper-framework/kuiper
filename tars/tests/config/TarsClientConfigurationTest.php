<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerBuilder;
use kuiper\event\EventConfiguration;
use kuiper\http\client\HttpClientConfiguration;
use kuiper\logger\LoggerConfiguration;
use kuiper\reflection\ReflectionConfiguration;
use kuiper\rpc\servicediscovery\InMemoryServiceResolver;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\serializer\SerializerConfiguration;
use kuiper\swoole\Application;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use kuiper\swoole\config\FoundationConfiguration;
use kuiper\tars\integration\QueryFServant;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TarsClientConfigurationTest extends TestCase
{
    public function testCreateClient()
    {
        $proxy = $this->createContainer([
            'application' => [
                'tars' => [
                    'client' => [
                        'options' => [
                            QueryFServant::class => [
                                'endpoint' => 'tars.tarsregistry.QueryObj@tcp -h 127.0.0.1 -p 17890',
                            ],
                        ],
                    ],
                ],
            ],
        ])->get(QueryFServant::class);
        // $ret = $proxy->findObjectById('winwin.option.OptionObj');
        // print_r($ret);
        $this->assertInstanceOf(QueryFServant::class, $proxy);
    }

    protected function createContainer(array $configArr): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 3);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ReflectionConfiguration());
        $builder->addConfiguration(new EventConfiguration());
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addConfiguration(new FoundationConfiguration());
        $builder->addConfiguration(new SerializerConfiguration());
        $builder->addConfiguration(new HttpClientConfiguration());
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        $builder->addConfiguration(new TarsClientConfiguration());
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions([
            ServiceResolverInterface::class => new InMemoryServiceResolver(),
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
        ]);

        return $app->getContainer();
    }
}
