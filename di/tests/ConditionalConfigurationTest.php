<?php

declare(strict_types=1);

namespace kuiper\di;

use function DI\value;
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

    public function testConditionalIsAlwaysOveride()
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new DependOnBarConfiguration());
        $builder->addDefinitions([
            'foo' => value('foo_value'),
        ]);
        $container = $builder->build();
        $foo = $container->get('foo');
        $this->assertEquals('foo_value', $foo);
    }

    public function testIgnoreCondition()
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($builder->getConfigurationDefinitionLoader()
            ->getDefinitions(new DependOnNonExistClassConfiguration(), true));
        $container = $builder->build();
        $foo = $container->get('foo');
        $this->assertEquals(['foo' => 'nonExistClass'], $foo);
    }
}
