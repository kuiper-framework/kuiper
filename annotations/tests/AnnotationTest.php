<?php

declare(strict_types=1);

namespace kuiper\annotations;

use kuiper\annotations\fixtures\Foo;
use kuiper\annotations\fixtures\Test;
use kuiper\web\TestCase;

class AnnotationTest extends TestCase
{
    public function testRead()
    {
        $reader = AnnotationReader::getInstance();
        $foo = $reader->getClassAnnotation(new \ReflectionClass(Test::class), Foo::class);
        print_r($foo);
        $foo = $reader->getPropertyAnnotation(new \ReflectionProperty(Test::class, 'foo'), Foo::class);
        print_r($foo);
    }
}
