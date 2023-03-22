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

namespace kuiper\web;

use function DI\autowire;

use kuiper\di\ContainerBuilder;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\swoole\config\FoundationConfiguration;
use kuiper\swoole\config\NyholmHttpMessageFactoryConfiguration;
use kuiper\web\security\Acl;
use kuiper\web\security\AclInterface;

class AnnotationProcessorTest extends TestCase
{
    public function testName()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addConfiguration(new FoundationConfiguration());
        $containerBuilder->addConfiguration(new NyholmHttpMessageFactoryConfiguration());
        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $containerBuilder->setReflectionNamespaceFactory($reflectionNs);
        $containerBuilder->componentScan([__NAMESPACE__.'\\fixtures']);
        $containerBuilder->addDefinitions([
            AclInterface::class => autowire(Acl::class),
        ]);
        $container = $containerBuilder->build();
        $app = SlimAppFactory::create($container);

        $processor = new AttributeProcessor($container, $app);
        $processor->process();
        $response = $app->handle($this->createRequest('GET /index'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
