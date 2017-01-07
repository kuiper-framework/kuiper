<?php

namespace kuiper\di;

use kuiper\di\fixtures\AnnotationFixture;
use kuiper\di\fixtures\AnnotationFixture2;
use kuiper\di\fixtures\AutowireProperty;

/**
 * Test class for Container.
 */
class ContainerAnnotationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        AnnotationFixture::$PARAMS = [];
    }

    public function createContainer()
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->addDefinitions([
            'foo' => 'foo value',
            'bar' => 'bar value',
            'bim' => new \stdClass(),
        ]);

        return $builder->build();
    }

    public function testAnnotation()
    {
        $container = $this->createContainer();
        $value = $container->get(AnnotationFixture::class);
        $this->assertAttributeEquals('foo value', 'property1', $value);
        $this->assertAttributeInstanceOf(AnnotationFixture2::class, 'property2', $value);
        $this->assertAttributeEquals('foo value', 'property3', $value);
        $this->assertAttributeSame(null, 'unannotatedProperty', $value);

        $calls = AnnotationFixture::$PARAMS;
        $this->assertEquals($calls[0], [
            AnnotationFixture::class.'::__construct', ['foo value', 'bar value'],
        ]);

        $this->assertEquals($calls[1], [
            AnnotationFixture::class.'::method1', [],
        ]);
        $this->assertEquals($calls[2], [
            AnnotationFixture::class.'::method2', ['foo value', 'bar value'],
        ]);
        $this->assertEquals($calls[3][0], AnnotationFixture::class.'::method3');
        $this->assertInstanceOf(AnnotationFixture2::class, $calls[3][1][0]);
        $this->assertInstanceOf(AnnotationFixture2::class, $calls[3][1][1]);
        $this->assertEquals($calls[4], [
            AnnotationFixture::class.'::method4', ['foo value', 'bar value'],
        ]);
        $this->assertEquals($calls[5][0], AnnotationFixture::class.'::optionalParameter');
        $this->assertSame($calls[5][1][0], $container->get('bim'));

        $this->assertEquals(count($calls[5][1]), 1);
    }

    public function testAutowire()
    {
        $value = $this->createContainer()->get(AutowireProperty::class);
        $this->assertAttributeInstanceOf(AnnotationFixture2::class, 'property1', $value);
        $this->assertAttributeInstanceOf(AnnotationFixture2::class, 'property2', $value);
        $this->assertAttributeEquals('foo value', 'property3', $value);
        // print_r($value);
    }
}
