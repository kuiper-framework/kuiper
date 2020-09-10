<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\ContainerBuilder;
use kuiper\di\fixtures\Foo;
use kuiper\di\fixtures\TwoConditionConfiguration;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use PHPUnit\Framework\TestCase;

class TwoConditionalTest extends TestCase
{
    public function testCondtionFoo()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new TwoConditionConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::fromArray([
                'foo' => 1,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertEquals('foo', $foo->getName());
    }

    public function testCondtionBar()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new TwoConditionConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::fromArray([
                'bar' => 1,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertEquals('bar', $foo->getName());
    }

    public function testLastConditionWin()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new TwoConditionConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::fromArray([
                'bar' => 1,
                'foo' => 1,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertEquals('bar', $foo->getName());
    }
}
