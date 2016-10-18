<?php
namespace kuiper\di;

use kuiper\test\TestCase;
use kuiper\di\ContainerBuilder;
use kuiper\di\source\DotArraySource;
use kuiper\di\source\ComponentSource;
use kuiper\reflection\ReflectionNamespaceFactory;

class ContainerBuilderTest extends TestCase
{
    public function createBuilder()
    {
        ReflectionNamespaceFactory::createInstance()
            ->register(__NAMESPACE__, __DIR__);
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addSource(new ComponentSource([__NAMESPACE__.'\\fixtures\\components'], $builder->getAnnotationReader()));
        return $builder;
    }

    public function testResolver()
    {
        $builder = $this->createBuilder();
        $builder->addSource(new DotArraySource([
            'db' => ['host' => 'localhost']
        ]));
        $container = $builder->build();
        // print_r($builder);
        $this->assertTrue($container->has(fixtures\components\FooServiceInterface::class));
        $this->assertTrue($container->has('db.host'));
        $this->assertEquals('localhost', $container->get('db.host'));
    }        
}