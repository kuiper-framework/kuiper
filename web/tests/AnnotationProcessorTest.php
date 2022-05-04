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
use kuiper\annotations\AnnotationReader;
use kuiper\di\ContainerBuilder;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use kuiper\swoole\config\FoundationConfiguration;
use kuiper\web\security\Acl;
use kuiper\web\security\AclInterface;

class AnnotationProcessorTest extends TestCase
{
    public function testName()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addConfiguration(new FoundationConfiguration());
        $containerBuilder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $containerBuilder->setReflectionNamespaceFactory($reflectionNs);
        $containerBuilder->componentScan([__NAMESPACE__.'\\fixtures']);
        $containerBuilder->addDefinitions([
            AclInterface::class => autowire(Acl::class),
        ]);
        $container = $containerBuilder->build();
        $annotationReader = AnnotationReader::getInstance();
        $app = SlimAppFactory::create($container);

        $processor = new AttributeProcessor($container, $annotationReader, $app);
        $processor->process();
        $response = $app->handle($this->createRequest('GET /index'));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
