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

class ReflectionNamespaceTest extends TestCase
{
    public function createFactory()
    {
        $class = new \ReflectionClass(ReflectionNamespaceFactory::class);
        $prop = $class->getProperty('INSTANCE');
        $prop->setValue(null);

        return ReflectionNamespaceFactory::getInstance();
    }

    public function testScanRegistered()
    {
        $factory = $this->createFactory();
        $factory->register($ns = __NAMESPACE__, __DIR__);
        $reflNs = $factory->create(__NAMESPACE__.'\\fixtures\\scanner');
        $classes = $reflNs->getClasses();
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\packageinfo',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C',
        ], $classes);
    }

    public function testScanLoader()
    {
        $factory = $this->createFactory();
        foreach ([__DIR__.'/../vendor/autoload.php', __DIR__.'/../../vendor/autoload.php'] as $file) {
            if (file_exists($file)) {
                $factory->registerLoader(require $file);
                break;
            }
        }
        $reflNs = $factory->create(__NAMESPACE__.'\\fixtures\\scanner');
        $classes = $reflNs->getClasses();
        sort($classes);
        $this->assertEquals([
            'kuiper\reflection\fixtures\scanner\pkg\A',
            'kuiper\reflection\fixtures\scanner\pkg\B',
            'kuiper\reflection\fixtures\scanner\pkg\packageinfo',
            'kuiper\reflection\fixtures\scanner\pkg\sub\C',
        ], $classes);
    }
}
