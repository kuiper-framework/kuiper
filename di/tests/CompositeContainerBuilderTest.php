<?php

namespace kuiper\di;

class CompositeContainerBuilderTest extends TestCase
{
    public function testBuild()
    {
        $builder = new CompositeContainerBuilder();
        $builder->root()
            ->addDefinitions(['kuiper\q\a' => 'root']);

        $builder->namespace('kuiper\q')
            ->addDefinitions(['kuiper\q\a' => 'a']);

        $container = $builder->build();
        $value = $container->get('kuiper\q\a');
        $this->assertEquals('a', $value);
    }
}
