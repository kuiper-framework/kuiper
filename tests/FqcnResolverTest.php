<?php
namespace kuiper\reflection;

use kuiper\test\TestCase;

class ClassResolverTest extends TestCase
{
    public function testResolveNotImported()
    {
        $file = new ReflectionFile(__DIR__.'/fixtures/DummyClass.php');
        $this->assertEquals(
            'kuiper\\reflection\\fixtures\\DummyInterface',
            $file->resolveClassName('DummyInterface')
        );
    }

    public function testResolveClassNameImported()
    {
        $file = new ReflectionFile(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            'kuiper\\reflection\\ReflectionFile',
            $file->resolveClassName('ReflectionFile')
        );
    }

    public function testResolveClassNameNotExists()
    {
        $file = new ReflectionFile(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            'kuiper\reflection\fixtures\NonExistClass',
            $file->resolveClassName('NonExistClass')
        );
    }

    public function testResolveClassNameMultipleNamespace()
    {
        $file = new ReflectionFile(__DIR__.'/fixtures/MultipleNamespaces.php');
        $this->assertEquals(
            'NamespaceA\\ClassA',
            $file->resolveClassName('ClassA', 'NamespaceB')
        );
        $this->assertEquals(
            'ClassC',
            $file->resolveClassName('ClassC', '')
        );
    }
}