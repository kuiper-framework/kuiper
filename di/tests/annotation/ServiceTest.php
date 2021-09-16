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

namespace kuiper\di\annotation;

use kuiper\annotations\AnnotationReader;
use kuiper\di\ContainerBuilder;
use kuiper\di\fixtures\FooService;
use kuiper\di\fixtures\FooServiceInterface;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    public function testAnnotation()
    {
        $containerBuilder = new ContainerBuilder();
        $reflectionClass = new \ReflectionClass(FooService::class);
        $reader = AnnotationReader::getInstance();
        /** @var Service $service */
        $service = $reader->getClassAnnotation($reflectionClass, Service::class);
        $service->setTarget($reflectionClass);
        $service->setContainerBuilder($containerBuilder);
        $service->handle();
        $container = $containerBuilder->build();
        $this->assertTrue($container->has(FooServiceInterface::class));
        $foo = $container->get(FooServiceInterface::class);
        $this->assertInstanceOf(FooService::class, $foo);
    }
}
