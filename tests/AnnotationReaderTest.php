<?php

namespace kuiper\annotations;

use kuiper\annotations\fixtures\annotation;
use kuiper\annotations\fixtures\DummyClass;
use ReflectionClass;

class AnnotationReaderTest extends TestCase
{
    protected function createReader()
    {
        return new AnnotationReader();
    }

    protected function getReflectionClass()
    {
        return new ReflectionClass(DummyClass::class);
    }

    public function testAnnotations()
    {
        $class = $this->getReflectionClass();
        $reader = $this->createReader();

        $classAnnotations = $reader->getClassAnnotations($class);
        $this->assertEquals(1, count($classAnnotations));
        // print_r($classAnnotations);
        $this->assertInstanceOf($annotName = annotation\DummyAnnotation::class, $annot = $reader->getClassAnnotation($class, $annotName));
        $this->assertEquals('hello', $annot->dummyValue);

        $field1Prop = $class->getProperty('field1');
        $propAnnots = $reader->getPropertyAnnotations($field1Prop);
        $this->assertEquals(1, count($propAnnots));
        $this->assertInstanceOf($annotName, $annot = $reader->getPropertyAnnotation($field1Prop, $annotName));
        $this->assertEquals('fieldHello', $annot->dummyValue);

        $getField1Method = $class->getMethod('getField1');
        $methodAnnots = $reader->getMethodAnnotations($getField1Method);
        $this->assertEquals(1, count($methodAnnots));
        $this->assertInstanceOf($annotName, $annot = $reader->getMethodAnnotation($getField1Method, $annotName));
        $this->assertEquals([1, 2, 'three'], $annot->value);

        $field2Prop = $class->getProperty('field2');
        $propAnnots = $reader->getPropertyAnnotations($field2Prop);
        $this->assertEquals(1, count($propAnnots));
        $this->assertInstanceOf($annotName = annotation\DummyJoinTable::class, $joinTableAnnot = $reader->getPropertyAnnotation($field2Prop, $annotName));
        $this->assertEquals(1, count($joinTableAnnot->joinColumns));
        $this->assertEquals(1, count($joinTableAnnot->inverseJoinColumns));
        $this->assertTrue($joinTableAnnot->joinColumns[0] instanceof annotation\DummyJoinColumn);
        $this->assertTrue($joinTableAnnot->inverseJoinColumns[0] instanceof annotation\DummyJoinColumn);
        $this->assertEquals('col1', $joinTableAnnot->joinColumns[0]->name);
        $this->assertEquals('col2', $joinTableAnnot->joinColumns[0]->referencedColumnName);
        $this->assertEquals('col3', $joinTableAnnot->inverseJoinColumns[0]->name);
        $this->assertEquals('col4', $joinTableAnnot->inverseJoinColumns[0]->referencedColumnName);

        $dummyAnnot = $reader->getMethodAnnotation($class->getMethod('getField1'), annotation\DummyAnnotation::class);
        $this->assertEquals('', $dummyAnnot->dummyValue);
        $this->assertEquals([1, 2, 'three'], $dummyAnnot->value);

        $dummyAnnot = $reader->getPropertyAnnotation($class->getProperty('field1'), annotation\DummyAnnotation::class);
        $this->assertEquals('fieldHello', $dummyAnnot->dummyValue);

        $classAnnot = $reader->getClassAnnotation($class, annotation\DummyAnnotation::class);
        $this->assertEquals('hello', $classAnnot->dummyValue);
    }

    public function testAnnotationsWithValidTargets()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithValidAnnotationTarget::class);

        $this->assertEquals(1, count($reader->getClassAnnotations($class)));
        $this->assertEquals(1, count($reader->getPropertyAnnotations($class->getProperty('foo'))));
        $this->assertEquals(1, count($reader->getMethodAnnotations($class->getMethod('someFunction'))));
        $this->assertEquals(1, count($reader->getPropertyAnnotations($class->getProperty('nested'))));
    }

    public function testAnnotationsWithVarType()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithAnnotationWithVarType::class);

        $this->assertEquals(1, count($fooAnnot = $reader->getPropertyAnnotations($class->getProperty('foo'))));
        $this->assertEquals(1, count($barAnnot = $reader->getMethodAnnotations($class->getMethod('bar'))));

        $this->assertInternalType('string', $fooAnnot[0]->string);
        $this->assertInstanceOf(annotation\AnnotationTargetAll::class, $barAnnot[0]->annotation);
    }

    public function testAtInDescription()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithAtInDescriptionAndAnnotation::class);

        $this->assertEquals(1, count($fooAnnot = $reader->getPropertyAnnotations($class->getProperty('foo'))));
        $this->assertEquals(1, count($barAnnot = $reader->getPropertyAnnotations($class->getProperty('bar'))));

        $this->assertInstanceOf(annotation\AnnotationTargetPropertyMethod::class, $fooAnnot[0]);
        $this->assertInstanceOf(annotation\AnnotationTargetPropertyMethod::class, $barAnnot[0]);
    }

    public function testClassWithValidEnum()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithAnnotationEnum::class);
        $annot = $reader->getMethodAnnotations($class->getMethod('bar'));
        $this->assertEquals($annot[0]->value, annotation\AnnotationEnum::TWO);
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Enum 'THREE' is invalid for attribute 'value', available: ["ONE","TWO"]
     */
    public function testClassWithEnumInvalid()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithInvalidAnnotationEnum::class);
        $annot = $reader->getMethodAnnotations($class->getMethod('bar'));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Constant 'kuiper\annotations\fixtures\annotation\AnnotationEnum::FOUR' is not defined for attribute 'value'
     */
    public function testClassWithEnumInvalidName()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithInvalidAnnotationEnumName::class);
        $annot = $reader->getMethodAnnotations($class->getMethod('bar'));
    }

    public function testClassWithWithDanglingComma()
    {
        $reader = $this->createReader();
        $annots = $reader->getClassAnnotations(new ReflectionClass(fixtures\ClassWithDanglingComma::class));

        $this->assertCount(1, $annots);
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Annotation @AnnotationTargetPropertyMethod is not allowed here. You may only use this annotation on these code elements: METHOD,PROPERTY
     */
    public function testClassWithInvalidAnnotationTargetAtClassDocBlock()
    {
        $reader = $this->createReader();
        $reader->getClassAnnotations(new \ReflectionClass(fixtures\ClassWithInvalidAnnotationTargetAtClass::class));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Annotation @AnnotationTargetClass is not allowed here. You may only use this annotation on these code elements: CLASS
     */
    public function testClassWithInvalidAnnotationTargetAtPropertyDocBlock()
    {
        $reader = $this->createReader();
        $reader->getPropertyAnnotations(new \ReflectionProperty(fixtures\ClassWithInvalidAnnotationTargetAtProperty::class, 'foo'));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Annotation @AnnotationTargetAnnotation is not allowed here. You may only use this annotation on these code elements: ANNOTATION
     */
    public function testClassWithInvalidNestedAnnotationTargetAtPropertyDocBlock()
    {
        $reader = $this->createReader();
        $reader->getPropertyAnnotations(new \ReflectionProperty(fixtures\ClassWithInvalidAnnotationTargetAtPropertyAnnotation::class, 'bar'));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Annotation @AnnotationTargetClass is not allowed here. You may only use this annotation on these code elements: CLASS
     */
    public function testClassWithInvalidAnnotationTargetAtMethodDocBlock()
    {
        $reader = $this->createReader();
        $reader->getMethodAnnotations(new \ReflectionMethod(fixtures\ClassWithInvalidAnnotationTargetAtMethod::class, 'functionName'));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage [Syntax Error] Expected namespace separator or identifier, got ')'
     */
    public function testClassWithAnnotationWithTargetSyntaxErrorAtClassDocBlock()
    {
        $reader = $this->createReader();
        $reader->getClassAnnotations(new \ReflectionClass(fixtures\ClassWithAnnotationWithTargetSyntaxError::class));
    }

    /**
     * @expectedException \kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage Attribute 'integer' expects int, got '"abc"'.
     */
    public function testClassWithPropertyInvalidVarTypeErrorProp()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithAnnotationWithInvalidVarTypeProp::class);

        $reader->getPropertyAnnotations($class->getProperty('invalidProperty'));
    }

    /**
     * @ expectedException kuiper\annotations\exception\AnnotationException
     * @expectedExceptionMessage expects kuiper\tests\annotations\annotation\AnnotationTargetAll, got 'kuiper\tests\annotations\annotation\AnnotationTargetAnnotation'
     */
    public function testClassWithMethodInvalidVarTypeError()
    {
        $reader = $this->createReader();
        $class = new ReflectionClass(fixtures\ClassWithAnnotationWithVarType::class);

        $reader->getMethodAnnotations($class->getMethod('bar'));
    }
}
