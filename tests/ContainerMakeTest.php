<?php

namespace kuiper\di;

use kuiper\di\definition\AliasDefinition;
use kuiper\di\fixtures\Class1CircularDependencies;
use kuiper\di\fixtures\InvalidScope;
use kuiper\di\fixtures\PassByReferenceDependency;
use kuiper\di\fixtures\Prototype;
use kuiper\di\fixtures\Singleton;
use stdClass;

/**
 * Test class for Container.
 *
 * @covers \DI\Container
 */
class ContainerMakeTest extends TestCase
{
    public function createContainer($definitions = [])
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    public function testSetMake()
    {
        $dummy = new stdClass();
        $container = $this->createContainer(['key' => $dummy]);
        $this->assertSame($dummy, $container->make('key'));
    }

    /**
     * @expectedException \kuiper\di\exception\NotFoundException
     */
    public function testMakeNotFound()
    {
        $container = $this->createContainer();
        $container->make('key');
    }

    public function testMakeWithClassName()
    {
        $container = $this->createContainer();
        $this->assertInstanceOf('stdClass', $container->make('stdClass'));
    }

    /**
     * Checks that the singleton scope is ignored.
     */
    public function testGetWithSingletonScope()
    {
        $container = $this->createContainer();
        // Without @Injectable annotation => default is Singleton
        $instance1 = $container->make('stdClass');
        $instance2 = $container->make('stdClass');
        $this->assertNotSame($instance1, $instance2);
        // With @Injectable(scope="singleton") annotation
        $instance3 = $container->make(Singleton::class);
        $instance4 = $container->make(Singleton::class);
        $this->assertNotSame($instance3, $instance4);
    }

    public function testMakeWithPrototypeScope()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        // With @Injectable(scope="prototype") annotation
        $instance1 = $container->make(Prototype::class);
        $instance2 = $container->make(Prototype::class);
        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * @expectedException \kuiper\di\exception\AnnotationException
     * @expectedExceptionMessage Constant 'kuiper\di\annotation\Injectable::FOOBAR' is not defined for attribute 'scope'
     * @coversNothing
     */
    public function testMakeWithInvalidScope()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        $container->make(InvalidScope::class);
    }

    /**
     * Tests if instantiation unlock works. We should be able to create two instances of the same class.
     */
    public function testCircularDependencies()
    {
        $container = $this->createContainer();
        $container->make(Prototype::class);
        $container->make(Prototype::class);
    }

    public function testCircularDependencyOk()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        $container->make(Class1CircularDependencies::class);
    }

    /**
     * @expectedException \kuiper\di\exception\DependencyException
     * @expectedExceptionMessage Circular dependency detected while trying to resolve entry 'foo'
     */
    public function testCircularDependencyExceptionWithAlias()
    {
        $container = $this->createContainer([
            'foo' => new AliasDefinition('foo'),
        ]);
        $container->make('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name parameter must be of type string
     */
    public function testNonStringParameter()
    {
        $container = $this->createContainer();
        $container->make(new stdClass());
    }

    /**
     * Tests a dependency can be made when a dependency is passed by reference.
     */
    public function testPassByReferenceParameter()
    {
        $container = $this->createContainer();
        $container->make(PassByReferenceDependency::class);
    }

    /**
     * Tests the parameter can be provided by reference.
     */
    public function testProvidedPassByReferenceParameter()
    {
        $container = $this->createContainer();

        $object = new stdClass();
        $container->make(PassByReferenceDependency::class, [&$object]);
        $this->assertEquals('bar', $object->foo);
    }
}
