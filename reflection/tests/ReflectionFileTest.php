<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\reflection;

use kuiper\reflection\fixtures\DummyClass;
use kuiper\reflection\fixtures\DummyInterface;
use NamespaceA\ClassA;
use NamespaceA\InterfaceA;
use NamespaceB\ClassB;
use NamespaceB\InterfaceB;

class ReflectionFileTest extends TestCase
{
    public function createReflectionFile($file): ReflectionFileInterface
    {
        return ReflectionFileFactory::getInstance()->create($file);
    }

    public function testAttribute(): void
    {
        $reflectionFile = $this->createReflectionFile(__DIR__.'/fixtures/TestAttribute.php');
        $this->assertEquals([
            'demo\integration\demo\CalculatorServant',
        ], $reflectionFile->getClasses());
    }

    public function testGetClasses(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyClass.php');
        $this->assertEquals(
            [DummyClass::class],
            $parser->getClasses()
        );
    }

    public function testGetInterfaces(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            [DummyInterface::class],
            $parser->getClasses()
        );
    }

    public function testGetClassesMultiple(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/MultipleNamespaces.php');
        $this->assertEquals(
            [
                0 => ClassA::class,
                1 => InterfaceA::class,
                2 => ClassB::class,
                3 => InterfaceB::class,
                4 => 'ClassC',
                5 => 'InterfaceC',
            ],
            $parser->getClasses()
        );
    }

    public function testGetImports(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUse.php');
        // var_export($parser->getImports());
        $this->assertEquals(
            [
                'Another' => 'My\\Full\\Classname',
                'NSname' => 'My\\Full\\NSname',
                'ArrayObject' => 'ArrayObject',
                'AnotherB' => 'My\\Full\\ClassnameB',
                'NSnameB' => 'My\\Full\\NSnameB',
                'ClassA' => 'some\\ns\\ClassA',
                'ClassB' => 'some\\ns\\ClassB',
                'C' => 'some\\ns\\ClassC',
            ],
            $parser->getImportedClasses('foo')
        );
        $this->assertEquals(
            [
                'functionName' => 'My\\Full\\functionName',
                'func' => 'My\\Full\\functionName',
                'fn_a' => 'some\\ns\\fn_a',
                'fn_b' => 'some\\ns\\fn_b',
                'fn_c' => 'some\\ns\\fn_c',
            ],
            $parser->getImportedFunctions('foo')
        );
        $this->assertEquals(
            [
                'CONSTANT' => 'My\\Full\\CONSTANT',
                'ConstA' => 'some\\ns\\ConstA',
                'ConstB' => 'some\\ns\\ConstB',
                'ConstC' => 'some\\ns\\ConstC',
            ],
            $parser->getImportedConstants('foo')
        );
    }

    public function testGetImports70(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUse70.php');
        $this->assertEquals(
            [
                'ClassA' => 'some\\ns\\ClassA',
                'C' => 'some\\ns\\ClassC',
            ],
            $parser->getImportedClasses('')
        );
    }

    public function testGetImportsMultiple(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/TestUseMulitipleNamespace.php');
        // var_export($parser->getImports());
        $this->assertEquals(
            [
                'Another' => 'My\\Full\\Classname',
            ],
            $parser->getImportedClasses('foo')
        );
        $this->assertEquals(
            [
                'Classname' => 'My\\Full\\Classname',
            ],
            $parser->getImportedClasses('bar')
        );
    }

    public function testGetTraits(): void
    {
        $parser = $this->createReflectionFile(__DIR__.'/fixtures/DummyTrait.php');
        $this->assertEquals(
            ['kuiper\\reflection\\fixtures\\DummyTrait'],
            $parser->getTraits()
        );
    }
}
