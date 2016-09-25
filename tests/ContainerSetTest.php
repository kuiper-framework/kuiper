<?php
namespace kuiper\di;

use Interop\Container\ContainerInterface;
use kuiper\di\Container;
use kuiper\di\ContainerBuilder;
use kuiper\di\definition\factory\AliasDefinitionFactory;

use kuiper\di\fixtures\Prototype;
use kuiper\di\fixtures\Singleton;
use kuiper\di\fixtures\InvalidScope;
use kuiper\di\fixtures\Class1CircularDependencies;
use kuiper\di\fixtures\PassByReferenceDependency;
use kuiper\test\TestCase;
use stdClass;

/**
 * Test class for Container.
 */
class ContainerSetTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);
        return $builder->build();
    }

    public function testSetGet()
    {
        $dummy = new stdClass();
        $container = $this->createContainer();
        $container->set('key', $dummy);
        $this->assertSame($dummy, $container->get('key'));
    }
}
