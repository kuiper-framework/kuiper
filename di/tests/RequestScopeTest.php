<?php

namespace kuiper\di;

class RequestScopeTest extends TestCase
{
    public function createContainer($definitions = [], $useAnnotations = false)
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);
        if ($useAnnotations) {
            $builder->useAnnotations(true);
        }

        return $builder->build();
    }

    public function test()
    {
        $container = $this->createContainer([
            fixtures\AutowiringFixture::class => object()->scope(Scope::REQUEST)
        ]);
        $container->startRequest();
        $obj = $container->get(fixtures\AutowiringFixture::class);
        $dummy = $container->get(fixtures\DummyClass::class);
        $container->endRequest();
        $container->startRequest();
        $this->assertNotSame($obj, $container->get(fixtures\AutowiringFixture::class));
        $this->assertNotSame($dummy, $container->get(fixtures\DummyClass::class));
    }
}
