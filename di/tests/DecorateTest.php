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

use function DI\decorate;
use function DI\value;
use PHPUnit\Framework\TestCase;

class DecorateTest extends TestCase
{
    public function testKuiperDecorate(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions([
            'foo' => value(42),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                // error_log("call double");
                return $value * 2;
            }),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                // error_log("call pow");
                return $value ** 2;
            }),
        ]);
        $container = $containerBuilder->build();

        $this->assertEquals(7056, $container->get('foo'));
    }

    public function testDIDecorate(): void
    {
        $containerBuilder = new \DI\ContainerBuilder();
        $containerBuilder->addDefinitions([
            'foo' => value(42),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                // error_log("call double");
                return $value * 2;
            }),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                // error_log("call pow");
                return $value ** 2;
            }),
        ]);
        $container = $containerBuilder->build();

        $this->assertEquals(7056, $container->get('foo'));
    }
}
