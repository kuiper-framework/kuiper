<?php

declare(strict_types=1);

namespace kuiper\annotations;

use kuiper\annotations\fixtures\Foo;
use kuiper\annotations\fixtures\Test;
use kuiper\di\ContainerBuilder;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use PHPUnit\Framework\TestCase;

class AnnotationConfigurationTest extends TestCase
{
    public function testName()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new AnnotationConfiguration());
        $builder->addDefinitions([
            PoolFactoryInterface::class => new PoolFactory(),
        ]);
        $container = $builder->build();
        $reader = $container->get(AnnotationReaderInterface::class);
        $this->assertInstanceOf(AnnotationReaderInterface::class, $reader);
        $annotations = $reader->getClassAnnotations(new \ReflectionClass(Test::class));
        //print_r($annotations);
        $this->assertCount(1, $annotations);
        $this->assertInstanceOf(Foo::class, $annotations[0]);
    }
}
