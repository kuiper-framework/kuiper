<?php

namespace kuiper\di;

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
        $container = $this->createContainer([
            'key' => (object) ['foo' => 'bar']
        ]);
        $old = $container->get('key');
        $container->set('key', $dummy = new stdClass);
        $this->assertSame($dummy, $container->get('key'));
    }
}
