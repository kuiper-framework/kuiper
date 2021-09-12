<?php

declare(strict_types=1);

namespace kuiper\annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;

class AnnotationReader implements AnnotationReaderInterface
{
    /**
     * @var AnnotationReaderInterface|null
     */
    private static $INSTANCE;

    /**
     * @var Reader
     */
    private $delegate;

    /**
     * @var Annotation[]
     */
    private static $ANNOTATIONS;

    /**
     * AnnotationReader constructor.
     */
    public function __construct(Reader $reader)
    {
        $this->delegate = $reader;
    }

    public static function getInstance(): AnnotationReaderInterface
    {
        if (null === self::$INSTANCE) {
            self::$INSTANCE = self::create();
        }

        return self::$INSTANCE;
    }

    public static function create(): AnnotationReaderInterface
    {
        AnnotationRegistry::registerLoader('class_exists');

        return new self(new \Doctrine\Common\Annotations\AnnotationReader());
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        $cacheKey = $class->getName();

        if (!isset(self::$ANNOTATIONS[$cacheKey])) {
            self::$ANNOTATIONS[$cacheKey] = $this->delegate->getClassAnnotations($class);
        }

        return self::$ANNOTATIONS[$cacheKey];
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

        if (!isset(self::$ANNOTATIONS[$cacheKey])) {
            self::$ANNOTATIONS[$cacheKey] = $this->delegate->getMethodAnnotations($method);
        }

        return self::$ANNOTATIONS[$cacheKey];
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

        if (!isset(self::$ANNOTATIONS[$cacheKey])) {
            self::$ANNOTATIONS[$cacheKey] = $this->delegate->getPropertyAnnotations($property);
        }

        return self::$ANNOTATIONS[$cacheKey];
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
