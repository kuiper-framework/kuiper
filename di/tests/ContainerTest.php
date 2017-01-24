<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface;
use stdClass;

/**
 * Test class for Container.
 */
class ContainerTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    public function testHas()
    {
        $container = $this->createContainer(['foo' => 'bar']);

        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('wow'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name parameter must be of type string
     */
    public function testHasNonStringParameter()
    {
        $container = $this->createContainer();
        $container->has(new stdClass());
    }

    /**
     * We should be able to set a null value.
     *
     * @see https://github.com/mnapoli/PHP-DI/issues/79
     */
    public function testSetNullValue()
    {
        $container = $this->createContainer(['foo' => null]);

        $this->assertNull($container->get('foo'));
    }

    /**
     * The container auto-registers itself.
     */
    public function testContainerIsRegistered()
    {
        $container = $this->createContainer();
        $this->assertSame($container, $container->get(Container::class));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }
}
