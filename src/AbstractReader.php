<?php

namespace kuiper\annotations;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class AbstractReader implements GreedyReaderInterface, ReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClassAnnotations(ReflectionClass $class)
    {
        $annotations = $this->getAnnotations($class);

        return $annotations['class'];
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        return $this->getFirst($this->getClassAnnotations($class), $annotationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotations(ReflectionMethod $method)
    {
        $annotations = $this->getAnnotations($method->getDeclaringClass());

        return isset($annotations['methods'][$method->getName()])
            ? $annotations['methods'][$method->getName()]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        return $this->getFirst($this->getMethodAnnotations($method), $annotationName);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        $annotations = $this->getAnnotations($property->getDeclaringClass());

        return isset($annotations['properties'][$property->getName()])
            ? $annotations['properties'][$property->getName()]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        return $this->getFirst($this->getPropertyAnnotations($property), $annotationName);
    }

    protected function getFirst($annotations, $annotationName)
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }
    }
}
