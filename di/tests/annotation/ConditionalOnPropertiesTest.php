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
use kuiper\di\fixtures\ConditionalOnPropertyConfiguration;
use kuiper\di\fixtures\Foo;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use PHPUnit\Framework\TestCase;

class ConditionalOnPropertiesTest extends TestCase
{
    public function testMatch(): void
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new ConditionalOnPropertyConfiguration());
        $builder->addDefinitions([
            PropertyResolverInterface::class => Properties::create([
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
