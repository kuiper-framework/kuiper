<?php

declare(strict_types=1);

namespace kuiper\di;

use kuiper\di\attribute\Component;
use kuiper\di\fixtures\scan\Foo;
use kuiper\di\fixtures\scan1\Bar;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionNamespaceFactory;
use PHPUnit\Framework\TestCase;

class ComponentScannerTest extends TestCase
{
    public function testScan(): void
    {
        ComponentCollection::clear();
        $reflectionNamespaceFactory = new ReflectionNamespaceFactory(ReflectionFileFactory::getInstance());
        $reflectionNamespaceFactory->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $containerBuilder = \Mockery::mock(ContainerBuilderInterface::class);
        $containerBuilder->shouldReceive('addDefinitions');
        $scanner = new ComponentScanner($containerBuilder, $reflectionNamespaceFactory);
        $scanner->scan([__NAMESPACE__.'\\fixtures\\scan']);
        $components = ComponentCollection::getComponents(Component::class);
        // $this->assertCount(2, $components);
        $this->assertEquals([
            0 => Foo::class,
            1 => Bar::class,
        ],
            array_map(static function (Component $component) {
                return $component->getTargetClass();
            }, $components));
    }
}
