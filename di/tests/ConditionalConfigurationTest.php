<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\di\fixtures\DependOnBarConfiguration;
use kuiper\di\fixtures\DependOnNonExistClassConfiguration;
use PHPUnit\Framework\TestCase;

class ConditionalConfigurationTest extends TestCase
{
    public function testBeanConfiguration()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new DependOnNonExistClassConfiguration());
        $builder->addConfiguration(new DependOnBarConfiguration());
        $container = $builder->build();
        $foo = $container->get('foo');
//        var_export($foo);
        $this->assertEquals(['foo' => 'bar'], $foo);
    }
}
