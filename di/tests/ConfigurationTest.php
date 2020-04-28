<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\di\fixtures\Bar;
use kuiper\di\fixtures\BeanConfiguration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testBeanConfiguration()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new BeanConfiguration());
        $container = $builder->build();
        $bar = $container->get(Bar::class);
        // var_export($bar);
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertEquals('bar', $bar->name);
    }

    public function testInject()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addConfiguration(new BeanConfiguration());
        $container = $builder->build();
        $bar = $container->get('foo');
        // var_export($bar);
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertEquals('other', $bar->name);
    }
}
