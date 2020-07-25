<?php

declare(strict_types=1);

namespace kuiper\annotations;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;

class AnnotationReader implements AnnotationReaderInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private static $INSTANCE;

    /**
     * @var Reader
     */
    private $delegate;

    /**
     * @var array
     */
    private $loadedAnnotations;

    /**
     * AnnotationReader constructor.
     */
    public function __construct(Reader $reader)
    {
        $this->delegate = $reader;
    }

    public static function getInstance(): AnnotationReaderInterface
    {
        if (!self::$INSTANCE) {
            AnnotationRegistry::registerLoader('class_exists');
            self::$INSTANCE = new self(new \Doctrine\Common\Annotations\AnnotationReader());
        }

        return self::$INSTANCE;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        $cacheKey = $class->getName();

        if (!isset($this->loadedAnnotations[$cacheKey])) {
            $this->loadedAnnotations[$cacheKey] = $this->delegate->getClassAnnotations($class);
        }

        return $this->loadedAnnotations[$cacheKey];
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $class = $method->getDeclaringClass();
        $cacheKey = $class->getName().'#'.$method->getName();

        if (!isset($this->loadedAnnotations[$cacheKey])) {
            $this->loadedAnnotations[$cacheKey] = $this->delegate->getMethodAnnotations($method);
        }

        return $this->loadedAnnotations[$cacheKey];
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $class = $property->getDeclaringClass();
        $cacheKey = $class->getName().'$'.$property->getName();

        if (!isset($this->loadedAnnotations[$cacheKey])) {
            $this->loadedAnnotations[$cacheKey] = $this->delegate->getPropertyAnnotations($property);
        }

        return $this->loadedAnnotations[$cacheKey];
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annot) {
            if ($annot instanceof $annotationName) {
                return $annot;
            }
        }

        return null;
    }
}
