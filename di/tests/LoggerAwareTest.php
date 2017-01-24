<?php

namespace kuiper\di;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test class for Container.
 */
class LoggerAwareTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    public function testAware()
    {
        $container = $this->createContainer([
            LoggerInterface::class => ($logger = new NullLogger()),
        ]);
        $obj = $container->get(fixtures\LoggerAwareObject::class);
        $this->assertEquals($logger, $this->readAttribute($obj, 'logger'));
    }
}
