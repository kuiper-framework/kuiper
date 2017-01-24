<?php

namespace kuiper\di;

/**
 * Test class for Container.
 */
class ContainerAwareTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    public function testAware()
    {
        $container = $this->createContainer();
        $obj = $container->get(fixtures\ContainerAwareObject::class);
        $this->assertEquals($container, $this->readAttribute($obj, 'container'));
    }
}
