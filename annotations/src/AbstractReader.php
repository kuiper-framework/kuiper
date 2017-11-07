<?php

namespace kuiper\annotations;

abstract class AbstractReader implements GreedyReaderInterface, ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->getAnnotations($class)->getClassAnnotations();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, string $annotationName)
    {
        return $this->getAnnotations($class)->getFirstClassAnnotation($annotationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return $this->getAnnotations($method->getDeclaringClass())
            ->getMethodAnnotations($method->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, string $annotationName)
    {
        return $this->getAnnotations($method->getDeclaringClass())
            ->getFirstMethodAnnotation($method->getName(), $annotationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        return $this->getAnnotations($property->getDeclaringClass())
            ->getPropertyAnnotations($property->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, string $annotationName)
    {
        return $this->getAnnotations($property->getDeclaringClass())
            ->getFirstPropertyAnnotation($property->getName(), $annotationName);
    }
}
