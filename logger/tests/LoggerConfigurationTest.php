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
    public function testLogger(): void
    {
        $container = $this->createContainer([]);
        $logger = $container->get(LoggerInterface::class);
        $logger->info('hello');
        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }

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
