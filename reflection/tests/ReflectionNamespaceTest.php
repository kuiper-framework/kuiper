<?php
namespace kuiper\reflection;

class ReflectionNamespaceTest extends TestCase
{
    public function createFactory()
    {
        $class = new \ReflectionClass(ReflectionNamespaceFactory::class);
        $prop = $class->getProperty('INSTANCE');
        $prop->setAccessible(true);
        $prop->setValue(null);
        return ReflectionNamespaceFactory::createInstance();
    }
    
    public function testScanRegistered()
    {
        $factory = $this->createFactory();
        $factory->register($ns = __NAMESPACE__, __DIR__);
        $reflNs = $factory->create(__NAMESPACE__ . '\\fixtures\\scanner');
        $classes = $reflNs->getClasses();
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\packageinfo',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C'
        ], $classes);
    }

    public function testScanLoader()
    {
        $factory = $this->createFactory();
        $factory->registerLoader(require(__DIR__.'/../vendor/autoload.php'));
        $reflNs = $factory->create(__NAMESPACE__ . '\\fixtures\\scanner');
        $classes = $reflNs->getClasses();
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\packageinfo',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C'
        ], $classes);
    }
}
