<?php

namespace kuiper\annotations;

use kuiper\reflection\ReflectionFileFactory;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParseClass()
    {
        $parser = new Parser(ReflectionFileFactory::createInstance());
        $annotations = $parser->parse(new \ReflectionClass(fixtures\DummyClass::class));
        //print_r($annotations);
        $this->assertInstanceOf(AnnotationSink::class, $annotations);
    }

    public function testParseTrait()
    {
        $parser = new Parser(ReflectionFileFactory::createInstance());
        $annotations = $parser->parse(new \ReflectionClass(fixtures\ClassWithTraitAnnotation::class));
        // print_r($annotations);
        $this->assertInstanceOf(AnnotationSink::class, $annotations);
    }
}
