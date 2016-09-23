<?php
namespace kuiper\reflection;

use kuiper\test\TestCase;

class ClassScannerTest extends TestCase
{
    public function createScanner()
    {
        return new ClassScanner;
    }

    public function testAllDisabled()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo(false);
        $classes = iterator_to_array($scanner->scan(__NAMESPACE__.'\\fixtures\\scanner'));
        $this->assertEmpty($classes);
    }
    
    public function testScanRegistered()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo(false);
        $scanner->register($ns = __NAMESPACE__.'\\fixtures\\scanner', __DIR__.'/fixtures/scanner');
        $classes = iterator_to_array($scanner->scan($ns));
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\packageinfo',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C'
        ], $classes);
    }

    public function testScanPackageInfo()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo();
        $classes = iterator_to_array($scanner->scan($ns = __NAMESPACE__.'\\fixtures\\scanner\pkg'));
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C'
        ], $classes);
    }

    public function testScanSubPackageInfo()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo();
        $classes = iterator_to_array($scanner->scan($ns = __NAMESPACE__.'\\fixtures\\scanner\\pkg\\sub'));
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\sub\C',
        ], $classes);
    }

    public function testScanIncludePath()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo(false);
        $scanner->enableIncludePath();
        set_include_path(get_include_path().PATH_SEPARATOR.__DIR__.'/fixtures');
        $classes = $scanner->scan('scanner');
        $this->assertEquals([
            'scanner\\Foo',
        ],iterator_to_array($classes));
    }

    public function testScanGetCurrentFile()
    {
        $scanner = $this->createScanner();
        $scanner->enablePackageInfo();
        foreach ($scanner->scan($ns = __NAMESPACE__.'\\fixtures\\scanner\\pkg') as $class) {
            $classes[$class] = $scanner->getFile();
        }
        $this->assertEquals([
            'kuiper\\reflection\\fixtures\\scanner\\pkg\\A' => __DIR__.'/fixtures/scanner/pkg/A.php',
            'kuiper\\reflection\\fixtures\\scanner\\pkg\\B' => __DIR__.'/fixtures/scanner/pkg/A.php',
            'kuiper\\reflection\\fixtures\\scanner\\pkg\\sub\\C' => __DIR__.'/fixtures/scanner/pkg/sub/C.php',
        ], $classes);
    }
}
