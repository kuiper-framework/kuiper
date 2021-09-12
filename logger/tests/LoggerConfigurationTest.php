<?php

declare(strict_types=1);

namespace kuiper\logger;

use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerConfigurationTest extends TestCase
{
    public function testLogger()
    {
        $container = $this->createContainer([]);
        $logger = $container->get(LoggerInterface::class);
        $logger->info('hello');
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }

    /**
     * @return ContainerInterface
     */
    protected function createContainer(array $config): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $config = Properties::create($config);
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addDefinitions([
            PropertyResolverInterface::class => $config,
        ]);

        return $builder->build();
    }
}
