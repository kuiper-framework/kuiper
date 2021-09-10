<?php

declare(strict_types=1);

namespace kuiper\annotations;

use kuiper\annotations\fixtures\Foo;
use kuiper\annotations\fixtures\FooImpl;
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

    public function testReadFromImpl()
    {
        $reader = AnnotationReader::getInstance();
        $method = new \ReflectionMethod(FooImpl::class, 'foo');
        if (false !== stripos($method->getDocComment(), '@inheritDoc')) {
            foreach ($method->getDeclaringClass()->getInterfaces() as $interface) {
                if ($interface->hasMethod($method->getName())) {
                    $method = $interface->getMethod($method->getName());
                    break;
                }
            }
        }
        $anno = $reader->getMethodAnnotation($method, Foo::class);
        $this->assertNotNull($anno);
    }
}
