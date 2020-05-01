<?php

declare(strict_types=1);

namespace kuiper\di;

use function DI\autowire;
use function DI\factory;
use kuiper\di\fixtures\Bar;
use PHPUnit\Framework\TestCase;

class FactoryDefinitionTest extends TestCase
{
    public function testFactory()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions([
            'foo' => factory([$this, 'create'])->parameter('param', 'foo'),
        ]);
        $container = $builder->build();
        $foo = $container->get('foo');
        $this->assertEquals($foo[0], 'foo');
    }

    public function testConstructor()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions([
            'foo' => autowire(Bar::class)
                ->constructorParameter(0, 'foo'),
        ]);
        $container = $builder->build();
        $foo = $container->get('foo');
        var_export($foo);
    }

    public function create($param)
    {
        return [$param];
    }
}
