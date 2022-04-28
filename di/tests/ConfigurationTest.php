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

namespace kuiper\di;

use kuiper\di\fixtures\Bar;
use kuiper\di\fixtures\BeanConfiguration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testBeanConfiguration(): void
    {
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new BeanConfiguration());
        $container = $builder->build();
        $bar = $container->get(Bar::class);
        // var_export($bar);
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertEquals('bar', $bar->name);
    }

    public function testInject(): void
    {
        $builder = new ContainerBuilder();
        $builder->useAttribute(true);
        $builder->addConfiguration(new BeanConfiguration());
        $container = $builder->build();
        $bar = $container->get('foo');
        // var_export($bar);
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertEquals('other', $bar->name);
    }
}
