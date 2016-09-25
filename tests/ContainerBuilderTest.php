<?php
namespace kuiper\di;

use kuiper\test\TestCase;
use kuiper\di\ContainerBuilder;
use kuiper\di\source\DotArraySource;

class ContainerBuilderTest extends TestCase
{
    public function createBuilder()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->getClassScanner()->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        return $builder;
    }

    public function testComponentScan()
    {
        $builder = $this->createBuilder();
        $builder->componentScan(__NAMESPACE__.'\\fixtures\\components');
        $container = $builder->build();
        // print_r($builder);
        $this->assertTrue($container->has(fixtures\components\FooServiceInterface::class));
        $this->assertTrue($container->has('foo'));
        $this->assertInstanceOf(fixtures\components\BarService::class, $container->get('foo'));
        $this->assertInstanceOf(fixtures\components\FooService::class,
                                $container->get(fixtures\components\FooServiceInterface::class));

    }

    public function testResolver()
    {
        $builder = $this->createBuilder();
        $builder->componentScan(__NAMESPACE__.'\\fixtures\\components');
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