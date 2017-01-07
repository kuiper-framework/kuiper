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
        $dummy = new stdClass();
        $container = $this->createContainer();
        $container->set('key', $dummy);
        $this->assertSame($dummy, $container->get('key'));
    }
}
