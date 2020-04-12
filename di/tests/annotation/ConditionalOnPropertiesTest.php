<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\ContainerBuilder;
use kuiper\di\fixtures\ConditionalOnPropertyConfiguration;
use kuiper\di\fixtures\Foo;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use PHPUnit\Framework\TestCase;

class ConditionalOnPropertiesTest extends TestCase
{
    public function testMatch()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ConditionalOnPropertyConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::fromArray([
                'foo' => 1,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $this->assertFalse($container->has('bar'));
        $this->assertTrue($container->has('foo1'));
        $this->assertFalse($container->has('foo2'));
        $foo = $container->get(Foo::class);
        $this->assertEquals('foo', $foo->getName());
        $foo1 = $container->get('foo1');
        $this->assertEquals('foo1', $foo1->getName());
    }
}
