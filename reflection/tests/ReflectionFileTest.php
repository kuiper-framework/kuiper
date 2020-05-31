<?php

declare(strict_types=1);

namespace kuiper\reflection;

class ReflectionFileTest extends TestCase
{
    public function createReflectionFile($file)
    {
        return ReflectionFileFactory::getInstance()->create($file);
    }

    public function testGetClasses()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyClass.php');
        $this->assertEquals(
            $parser->getClasses(),
            ['kuiper\\reflection\\fixtures\\DummyClass']
        );
    }

    public function testGetInterfaces()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            $parser->getClasses(),
            ['kuiper\\reflection\\fixtures\\DummyInterface']
        );
    }

    public function testGetClassesMultiple()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/MultipleNamespaces.php');
        $this->assertEquals(
            $parser->getClasses(),
            [
                0 => 'NamespaceA\\ClassA',
                1 => 'NamespaceA\\InterfaceA',
                2 => 'NamespaceB\\ClassB',
                3 => 'NamespaceB\\InterfaceB',
                4 => 'ClassC',
                5 => 'InterfaceC',
            ]
        );
    }

    public function testGetImports()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUse.php');
        // var_export($parser->getImports());
        $this->assertEquals(
            $parser->getImportedClasses('foo'),
            [
                'Another' => 'My\\Full\\Classname',
                'NSname' => 'My\\Full\\NSname',
                'ArrayObject' => 'ArrayObject',
                'AnotherB' => 'My\\Full\\ClassnameB',
                'NSnameB' => 'My\\Full\\NSnameB',
                'ClassA' => 'some\\ns\\ClassA',
                'ClassB' => 'some\\ns\\ClassB',
                'C' => 'some\\ns\\ClassC',
            ]
        );
        $this->assertEquals(
            $parser->getImportedFunctions('foo'),
            [
                'functionName' => 'My\\Full\\functionName',
                'func' => 'My\\Full\\functionName',
                'fn_a' => 'some\\ns\\fn_a',
                'fn_b' => 'some\\ns\\fn_b',
                'fn_c' => 'some\\ns\\fn_c',
            ]
        );
        $this->assertEquals(
            $parser->getImportedConstants('foo'),
            [
                'CONSTANT' => 'My\\Full\\CONSTANT',
                'ConstA' => 'some\\ns\\ConstA',
                'ConstB' => 'some\\ns\\ConstB',
                'ConstC' => 'some\\ns\\ConstC',
            ]
        );
    }

    public function testGetImports70()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUse70.php');
        $this->assertEquals(
            $parser->getImportedClasses(''),
            [
                'ClassA' => 'some\\ns\\ClassA',
                'C' => 'some\\ns\\ClassC',
            ]
        );
    }

    public function testGetImportsMultiple()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUseMulitipleNamespace.php');
        // var_export($parser->getImports());
        $this->assertEquals(
            $parser->getImportedClasses('foo'),
            [
                'Another' => 'My\\Full\\Classname',
            ]
        );
        $this->assertEquals(
            $parser->getImportedClasses('bar'),
            [
                'Classname' => 'My\\Full\\Classname',
            ]
        );
    }

    public function testGetTraits()
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyTrait.php');
        $this->assertEquals(
            $parser->getTraits(),
            ['kuiper\\reflection\\fixtures\\DummyTrait']
        );
    }
}
