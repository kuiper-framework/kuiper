<?php

namespace kuiper\di;

use kuiper\di\definition\ObjectDefinition;
use ProxyManager\Proxy\VirtualProxyInterface;

/**
 * Test class for Container.
 */
class LazyTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    public function testLazy()
    {
        $container = $this->createContainer([
            'foo' => (new ObjectDefinition(fixtures\DummyClass::class))
            ->lazy(),
            'bar' => new ObjectDefinition(fixtures\DummyClass::class),
        ]);
        $ret = $container->get('foo');
        $this->assertInstanceOf(VirtualProxyInterface::class, $ret);
        $this->assertInstanceOf(fixtures\DummyClass::class, $ret);
        // $ret->foo();
        $ret = $container->get('bar');
        $this->assertInstanceOf(fixtures\DummyClass::class, $ret);
        $this->assertNotInstanceOf(VirtualProxyInterface::class, $ret);
        // print_r($ret);
    }
}
