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

class FqcnResolverTest extends TestCase
{
    public function createResolver($file)
    {
        return new FqcnResolver(ReflectionFileFactory::getInstance()->create($file));
    }

    public function testResolveNotImported()
    {
        $file = $this->createResolver(__DIR__.'/fixtures/DummyClass.php');
        $this->assertEquals(
            'kuiper\\reflection\\fixtures\\DummyInterface',
            $file->resolve('DummyInterface', __NAMESPACE__.'\\fixtures')
        );
    }

    public function testResolveClassNameImported()
    {
        $file = $this->createResolver(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            'kuiper\\reflection\\ReflectionFile',
            $file->resolve('ReflectionFile', __NAMESPACE__.'\\fixtures')
        );
    }

    public function testResolveClassNameNotExists()
    {
        $file = $this->createResolver(__DIR__.'/fixtures/DummyInterface.php');
        $this->assertEquals(
            'kuiper\reflection\fixtures\NonExistClass',
            $file->resolve('NonExistClass', __NAMESPACE__.'\\fixtures')
        );
    }

    public function testResolveClassNameMultipleNamespace()
    {
        $file = $this->createResolver(__DIR__.'/fixtures/MultipleNamespaces.php');
        $this->assertEquals(
            'NamespaceA\\ClassA',
            $file->resolve('ClassA', 'NamespaceB')
        );
        $this->assertEquals(
            'ClassC',
            $file->resolve('ClassC', '')
        );
    }

    public function testResolveClassNameImportNamespace()
    {
        $file = $this->createResolver(__DIR__.'/fixtures/ns/ImportNs.php');
        $this->assertEquals(
            'kuiper\reflection\fixtures\DummyClass',
            $file->resolve('fixtures\DummyClass', 'kuiper\reflection\fixtures\ns')
        );
    }
}
