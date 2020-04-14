<?php

declare(strict_types=1);

namespace kuiper\di;

use function DI\decorate;
use function DI\value;
use PHPUnit\Framework\TestCase;

class DecorateTest extends TestCase
{
    public function testName()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions([
            'foo' => value(42),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                return $value * 2;
            }),
        ]);
        $container = $containerBuilder->build();

        $this->assertEquals(84, $container->get('foo'));
    }

    public function testDecorate()
    {
        $containerBuilder = new \DI\ContainerBuilder();
        $containerBuilder->addDefinitions([
            'foo' => value(42),
        ]);
        $containerBuilder->addDefinitions([
            'foo' => decorate(function ($value) {
                return $value * 2;
            }),
        ]);
        $container = $containerBuilder->build();

        $this->assertEquals(84, $container->get('foo'));
    }
}
