<?php

namespace kuiper\di;

use Interop\Container\ContainerInterface;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\NamedParameters;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\fixtures\Class1CircularDependencies;
use kuiper\di\fixtures\InvalidScope;
use kuiper\di\fixtures\PassByReferenceDependency;
use kuiper\di\fixtures\Prototype;
use kuiper\di\fixtures\Singleton;
use stdClass;

/**
 * Test class for Container.
 */
class ContainerGetTest extends TestCase
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
        $container = $this->createContainer(['key' => $dummy]);
        $this->assertSame($dummy, $container->get('key'));
    }

    /**
     * @expectedException \kuiper\di\exception\NotFoundException
     */
    public function testGetNotFound()
    {
        $container = $this->createContainer();
        $container->get('key');
    }

    /**
     * @coversNothing
     */
    public function testClosureIsResolved()
    {
        $closure = function () {
            return 'hello';
        };
        $container = $this->createContainer(['key' => $closure]);
        $this->assertEquals('hello', $container->get('key'));
    }

    public function testGetWithClassName()
    {
        $container = $this->createContainer();
        $this->assertInstanceOf('stdClass', $container->get('stdClass'));
    }

    public function testGetWithPrototypeScope()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        // With @Injectable(scope="prototype") annotation
        $instance1 = $container->get(Prototype::class);
        $instance2 = $container->get(Prototype::class);
        $this->assertNotSame($instance1, $instance2);
    }

    public function testGetWithSingletonScope()
    {
        $container = $this->createContainer();
        // Without @Injectable annotation => default is Singleton
        $instance1 = $container->get('stdClass');
        $instance2 = $container->get('stdClass');
        $this->assertSame($instance1, $instance2);
        // With @Injectable(scope="singleton") annotation
        $instance3 = $container->get(Singleton::class);
        $instance4 = $container->get(Singleton::class);
        $this->assertSame($instance3, $instance4);
    }

    /**
     * @expectedException \kuiper\di\exception\AnnotationException
     * @expectedExceptionMessage Constant 'kuiper\di\annotation\Injectable::FOOBAR' is not defined for attribute 'scope'
     * @coversNothing
     */
    public function testGetWithInvalidScope()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        $container->get(InvalidScope::class);
    }

    /**
     * Tests if instantiation unlock works. We should be able to create two instances of the same class.
     */
    public function testCircularDependencies()
    {
        $container = $this->createContainer();
        $container->get(Prototype::class);
        $container->get(Prototype::class);
    }

    public function testCircularDependencyOk()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $container = $builder->build();
        $container->get(Class1CircularDependencies::class);
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
        $container->get('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The name parameter must be of type string
     */
    public function testNonStringParameter()
    {
        $container = $this->createContainer();
        $container->get(new stdClass());
    }

    /**
     * Tests a class can be initialized with a parameter passed by reference.
     */
    public function testPassByReferenceParameter()
    {
        $container = $this->createContainer();
        $object = $container->get(PassByReferenceDependency::class);
        $this->assertInstanceOf(PassByReferenceDependency::class, $object);
    }

    public function testConstructorOptionParam()
    {
        $container = $this->createContainer([
            'foo' => (new ObjectDefinition(fixtures\ClassConstructor::class))
            ->constructor(new NamedParameters(['param2' => 1])),
        ]);
        $object = $container->get('foo');
        // print_r($object);
        $this->assertEquals(1, $object->param2);
    }

    public function testFactoryParameters()
    {
        $container = $this->createContainer([
            'foo' => function (fixtures\DummyClass $object) {
                return $object;
            },
        ]);
        $object = $container->get('foo');
        $this->assertInstanceOf(fixtures\DummyClass::class, $object);
    }

    public function testDeferInit()
    {
        fixtures\DummyClass::$calls = [];
        $container = $this->createContainer([
            'foo' => (new ObjectDefinition(fixtures\DummyClass::class))
            ->method('foo'),
            'bar' => (new ObjectDefinition(fixtures\DeferredConstructor::class))
            ->constructor(new AliasDefinition('foo')),
        ]);
        $foo = $container->get('foo');
        $bar = $container->get('bar');
        // print_r([$bar, fixtures\DummyClass::$calls]);
        $this->assertEquals(1, count(fixtures\DummyClass::$calls));
    }

    public function testScopeRequest()
    {
        $container = $this->createContainer([
            'foo' => (new ObjectDefinition(fixtures\DummyClass::class))
            ->scope(Scope::REQUEST),
        ]);
        $container->startRequest();
        $foo = $container->get('foo');
        $foo->setName('foo');
        $this->assertEquals('foo', $foo->getName());

        $container->startRequest();
        $foo = $container->get('foo');
        $this->assertNull($foo->getName());
        // print_r([$real, $real2]);
    }

    public function testFactoryDefaultParams()
    {
        $container = $this->createContainer([
            'foo' => (new FactoryDefinition(function ($param = []) {
                return $param;
            }))->scope(Scope::PROTOTYPE),
            'bar' => (new FactoryDefinition(function (ContainerInterface $c) {
                return $c;
            }))->scope(Scope::PROTOTYPE),
        ]);
        $ret = $container->make('foo');
        $this->assertEquals([], $ret);
        $ret = $container->make('foo', [$obj = new \stdClass()]);
        $this->assertSame($obj, $ret);

        $ret = $container->make('bar');
        $this->assertSame($container, $ret);
    }
}
