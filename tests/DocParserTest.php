<?php

namespace kuiper\annotations;

use kuiper\reflection\ReflectionFile;

class DocParserTest extends TestCase
{
    public function createTestParser()
    {
        $parser = new DocParser();

        return $parser;
    }

    public function testNestedArraysWithNestedAnnotation()
    {
        $parser = $this->createTestParser();
        $doc = '@Name(foo={1,2, {"key"=@Name}})';

        // Nested arrays with nested annotations
        $result = $parser->parse($doc, new ReflectionFile(__FILE__), null, 1);
        $annot = $result[0];

        $this->assertTrue($annot instanceof Annotation);
        $this->assertEquals('Name', $annot->getName());
        $args = $annot->getArguments();
        // print_r($args);
        $this->assertEquals(3, count($args['foo']));
        $this->assertEquals(1, $args['foo'][0]);
        $this->assertEquals(2, $args['foo'][1]);
        $this->assertTrue(is_array($args['foo'][2]));

        $nestedArray = $args['foo'][2];
        $this->assertTrue(isset($nestedArray['key']));
        $this->assertTrue($nestedArray['key'] instanceof Annotation);
    }

    public function testBasicAnnotations()
    {
        $parser = $this->createTestParser();

        // Marker annotation
        $result = $parser->parse('@Name', new ReflectionFile(__FILE__), null, 1);
        $annot = $result[0];
        $this->assertTrue($annot instanceof Annotation);
        $this->assertEquals('Name', $annot->getName());
        $this->assertEquals([], $annot->getArguments());

        // Associative arrays
        $result = $parser->parse('@Name(foo={"key1" = "value1"})', new ReflectionFile(__FILE__), null, 1);
        $annot = $result[0];
        $this->assertTrue(is_array($annot->getArguments()['foo']));
        $this->assertTrue(isset($annot->getArguments()['foo']['key1']));

        // Numerical arrays
        $result = $parser->parse('@Name({2="foo", 4="bar"})', new ReflectionFile(__FILE__), null, 1);
        $args = $result[0]->getArguments()['value'];
        $this->assertEquals('foo', $args[2]);
        $this->assertEquals('bar', $args[4]);
        $this->assertFalse(isset($args[0]));
        $this->assertFalse(isset($args[1]));
        $this->assertFalse(isset($args[3]));

        // Multiple values
        $result = $parser->parse('@Name(@Name, @Name)', new ReflectionFile(__FILE__), null, 1);
        $args = $result[0]->getArguments()['value'];
        $this->assertTrue($args[0] instanceof Annotation);
        $this->assertTrue($args[1] instanceof Annotation);

        // Multiple types as values
        $result = $parser->parse('@Name(foo="Bar", @Name, {"key1"="value1", "key2"="value2"})', new ReflectionFile(__FILE__), null, 1);
        $args = $result[0]->getArguments();
        // print_r($args); return;

        $this->assertTrue($args['value'][0] instanceof Annotation);
        $this->assertTrue(is_array($args['value'][1]));
        $this->assertEquals('value1', $args['value'][1]['key1']);
        $this->assertEquals('value2', $args['value'][1]['key2']);

        // Complete docblock
        $docblock = <<<'DOCBLOCK'
/**
 * Some nifty class.
 *
 * @author Mr.X
 * @Name(foo="bar")
 */
DOCBLOCK;

        $result = $parser->parse($docblock, new ReflectionFile(__FILE__), null, 1);
        $this->assertEquals(2, count($result));
    }
}
