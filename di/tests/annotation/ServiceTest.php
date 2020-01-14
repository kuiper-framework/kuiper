<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use kuiper\di\ContainerBuilder;
use kuiper\di\fixtures\FooService;
use kuiper\di\fixtures\FooServiceInterface;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    public function testAnnotation()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $containerBuilder = new ContainerBuilder();
        $reflectionClass = new \ReflectionClass(FooService::class);
        $reader = new AnnotationReader();
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
