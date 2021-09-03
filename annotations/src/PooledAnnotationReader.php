<?php

declare(strict_types=1);

namespace kuiper\annotations;

use Doctrine\Common\Annotations\AnnotationRegistry;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\pool\PoolInterface;

class PooledAnnotationReader implements AnnotationReaderInterface
{
    /**
     * @var PoolInterface
     */
    private $pool;

    public function __construct(PoolFactoryInterface $poolFactory)
    {
        $this->pool = $poolFactory->create('AnnotationReaderPool', function (): AnnotationReaderInterface {
            AnnotationRegistry::registerLoader('class_exists');

            return new AnnotationReader(new \Doctrine\Common\Annotations\AnnotationReader());
        });
    }

    private function getAnnotationReader(): AnnotationReaderInterface
    {
        return $this->pool->take();
    }

    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->getAnnotationReader()->getClassAnnotations($class);
    }

    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return $this->getAnnotationReader()->getClassAnnotation($class, $annotationName);
    }

    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->getAnnotationReader()->getMethodAnnotations($method);
    }

    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        return $this->getAnnotationReader()->getMethodAnnotation($method, $annotationName);
    }

    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->getAnnotationReader()->getPropertyAnnotations($property);
    }

    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return $this->getAnnotationReader()->getPropertyAnnotation($property, $annotationName);
    }
}
