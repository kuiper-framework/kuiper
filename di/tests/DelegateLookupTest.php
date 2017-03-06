<?php

namespace kuiper\di;

use Psr\Container\ContainerInterface;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\NamedParameters;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\fixtures\Class1CircularDependencies;
use kuiper\di\fixtures\InvalidScope;
use kuiper\di\fixtures\PassByReferenceDependency;
use kuiper\di\fixtures\Prototype;
use kuiper\di\fixtures\Singleton;
use stdClass;

class DelegateLookupTest extends TestCase
{
    public function createContainer(array $parentDefs, array $defs, $useAnnotations = false)
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($parentDefs);
        $parentContainer = $builder->build();
        $builder = new ContainerBuilder();
        $builder->setParentContainer($parentContainer);
        $builder->addDefinitions($defs);
        if ($useAnnotations) {
            $builder->useAnnotations(true);
        }
        return $builder->build();
    }

    public function testGet()
    {
        $container = $this->createContainer([
            'foo' => factory(function () {
                return 'parent';
            }),
            'bar' => 'parent'
        ], [
            'foo' => factory(function () {
                return 'child';
            }),
            'baz' => 'child'
        ]);
        $this->assertEquals([
            'child',
            'parent',
            'child'
        ], [
            $container->get('foo'),
            $container->get('bar'),
            $container->get('baz'),
        ]);
    }
}
