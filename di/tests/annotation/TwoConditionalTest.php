<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function testConditionFoo(): void
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

    public function testConditionBar(): void
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

    public function testLastConditionWin(): void
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
