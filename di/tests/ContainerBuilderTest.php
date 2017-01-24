<?php

namespace kuiper\di;

use kuiper\di\source\ComponentSource;
use kuiper\di\source\DotArraySource;
use kuiper\reflection\ReflectionNamespaceFactory;

class ContainerBuilderTest extends TestCase
{
    public function createBuilder()
    {
        return $builder = new ContainerBuilder();
    }

    public function testResolver()
    {
        $builder = $this->createBuilder();
        ReflectionNamespaceFactory::createInstance()
            ->register(__NAMESPACE__, __DIR__);
        $builder->useAnnotations(true);
        $builder->addSource(new ComponentSource([__NAMESPACE__.'\\fixtures\\components'], $builder->getAnnotationReader()));

        $builder->addSource(new DotArraySource([
            'db' => ['host' => 'localhost'],
        ]));
        $container = $builder->build();
        // print_r($builder);
        $this->assertTrue($container->has(fixtures\components\FooServiceInterface::class));
        $this->assertTrue($container->has('db.host'));
        $this->assertEquals('localhost', $container->get('db.host'));
    }

    public function testClosure()
    {
        $builder = $this->createBuilder();
        $obj = (object) [];
        $builder->addDefinitions([
            'foo' => function () use ($obj) {
                return $obj;
            },
        ]);
        $container = $builder->build();
        $this->assertEquals($obj, $container->get('foo'));
    }

    public function testValue()
    {
        $builder = $this->createBuilder();
        $obj = (object) [];
        $builder->addDefinitions([
            'foo' => $obj,
        ]);
        $container = $builder->build();
        $this->assertEquals($obj, $container->get('foo'));
    }
}
