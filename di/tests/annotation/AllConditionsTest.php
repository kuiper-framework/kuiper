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
use kuiper\di\fixtures\AllConditionsConfiguration;
use kuiper\di\fixtures\Foo;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use PHPUnit\Framework\TestCase;

class AllConditionsTest extends TestCase
{
    public function testMatchFoo1(): void
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new AllConditionsConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::create([
                'foo' => 'foo1',
                'foo1' => true,
                'foo2' => true,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertEquals('foo1', $foo->getName());
    }

    public function testMatchFoo2(): void
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new AllConditionsConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::create([
                'foo' => 'foo2',
                'foo1' => true,
                'foo2' => true,
            ]),
        ]);
        $container = $builder->build();
        $this->assertTrue($container->has(Foo::class));
        $foo = $container->get(Foo::class);
        $this->assertEquals('foo2', $foo->getName());
    }
}
