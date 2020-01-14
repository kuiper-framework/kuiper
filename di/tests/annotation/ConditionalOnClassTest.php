<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\ContainerBuilder;
use kuiper\di\fixtures\ConditionalOnClassConfiguration;
use kuiper\di\fixtures\Foo;
use PHPUnit\Framework\TestCase;

class ConditionalOnClassTest extends TestCase
{
    public function testMatch()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ConditionalOnClassConfiguration());
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $this->assertFalse($container->has('bar'));
        $foo = $container->get(Foo::class);
        $this->assertEquals('foo', $foo->getName());
    }
}
