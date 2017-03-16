<?php

namespace kuiper\di;

use Mockery;

class CompositeContainerTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    public function testFirstNamespaceMatch()
    {
        $container = new CompositeContainer($containers = [
            'kuiper\q' => Mockery::mock(ContainerInterface::class),
            'kuiper\q\p' => Mockery::mock(ContainerInterface::class),
            'kuiper\a' => Mockery::mock(ContainerInterface::class),
        ]);
        // var_export($this->readAttribute($container, 'namespaces')); return;

        $subcontainer = $containers['kuiper\q\p'];
        $subcontainer->shouldReceive('has')
            ->andReturn(true);
        $subcontainer->shouldReceive('get');

        $container->get('kuiper\q\p\test');
    }

    public function testFirstNamespaceNotMatch()
    {
        $container = new CompositeContainer($containers = [
            'kuiper\q' => Mockery::mock(ContainerInterface::class),
            'kuiper\q\p' => Mockery::mock(ContainerInterface::class),
            'kuiper\a' => Mockery::mock(ContainerInterface::class),
        ]);

        $subcontainer = $containers['kuiper\q\p'];
        $subcontainer->shouldReceive('has')
            ->andReturn(false);
        $subcontainer->shouldNotReceive('get');

        $subcontainer = $containers['kuiper\q'];
        $subcontainer->shouldReceive('has')
            ->andReturn(true);
        $subcontainer->shouldReceive('get');

        $container->get('kuiper\q\p\test');
    }

    public function testAllNamespaceNotMatch()
    {
        $container = new CompositeContainer($containers = [
            'kuiper\q' => Mockery::mock(ContainerInterface::class),
            'kuiper\q\p' => Mockery::mock(ContainerInterface::class),
            'kuiper\a' => Mockery::mock(ContainerInterface::class),
        ]);

        $subcontainer = $containers['kuiper\q\p'];
        $subcontainer->shouldReceive('has')
            ->andReturn(false);
        $subcontainer->shouldNotReceive('get');

        $subcontainer = $containers['kuiper\q'];
        $subcontainer->shouldReceive('has')
            ->andReturn(false);
        $subcontainer->shouldNotReceive('get');

        $subcontainer = $containers['kuiper\a'];
        $subcontainer->shouldReceive('has')
            ->andReturn(true);
        $subcontainer->shouldReceive('get');

        $container->get('kuiper\q\p\test');
    }

    public function testNoNamespace()
    {
        $container = new CompositeContainer($containers = [
            'kuiper\q' => Mockery::mock(ContainerInterface::class),
            'kuiper\q\p' => Mockery::mock(ContainerInterface::class),
            'kuiper\a' => Mockery::mock(ContainerInterface::class),
        ]);
        $subcontainer = $containers['kuiper\q'];
        $subcontainer->shouldReceive('has')
             ->andReturn(true);
        $subcontainer->shouldReceive('get');
        $container->get('settings');
    }
}
