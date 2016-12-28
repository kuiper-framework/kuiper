<?php

namespace kuiper\annotations;

use kuiper\annotations\annotation\Target;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionFileInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AnnotationContext
{
    /**
     * @var ReflectionClass
     */
    private $class;

    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @var ReflectionMethod
     */
    private $method;

    /**
     * @var ReflectionProperty
     */
    private $property;

    /**
     * @var ReflectionFileFactoryInterface
     */
    private $reflectionFileFactory;

    /**
     * @var ReflectionFileInterface
     */
    private $reflectionFile;

    /**
     * @var int constant defined in annotation\Target
     */
    private $target;

    /**
     * @var Annotation
     */
    private $annotation;

    /**
     * @var string
     */
    private $annotationClassName;

    public function __construct(ReflectionClass $class, ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->class = $class;
        $this->declaringClass = $class;
        $this->target = Target::TARGET_CLASS;
        $this->reflectionFileFactory = $reflectionFileFactory;
        $this->reflectionFile = $reflectionFileFactory->create($class->getFileName());
    }

    /**
     * Creates new context with given method.
     *
     * @param ReflectionMethod $method
     *
     * @return static
     */
    public function withMethod(ReflectionMethod $method)
    {
        $context = clone $this;
        $context->method = $method;
        $context->declaringClass = $method->getDeclaringClass();
        $context->target = Target::TARGET_METHOD;
        $context->reflectionFile = $this->reflectionFileFactory->create($context->declaringClass->getFileName());

        return $context;
    }

    /**
     * Creates new context with given property.
     *
     * @param ReflectionProperty $property
     *
     * @return static
     */
    public function withProperty(ReflectionProperty $property)
    {
        $context = clone $this;
        $context->property = $property;
        $context->declaringClass = $property->getDeclaringClass();
        $context->target = Target::TARGET_PROPERTY;
        $context->reflectionFile = $this->reflectionFileFactory->create($context->declaringClass->getFileName());

        return $context;
    }

    /**
     * Creates new context with given annotation.
     *
     * @param Annotation $annotation
     * @param int        $target
     *
     * @return static
     */
    public function withAnnotation(Annotation $annotation, $target = null)
    {
        $context = clone $this;
        $context->annotation = $annotation;
        $context->annotationClassName = null;
        if (isset($target)) {
            $context->target = $target;
        }

        return $context;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getAnnotation()
    {
        return $this->annotation;
    }

    public function getAnnotationClassName()
    {
        if (!isset($this->annotationClassName)) {
            $resolver = new FqcnResolver($this->reflectionFile);
            $this->annotationClassName = $resolver->resolve($this->annotation->getName(), $this->declaringClass->getNamespaceName());
        }

        return $this->annotationClassName;
    }

    public function getFile()
    {
        return $this->reflectionFile->getFile();
    }

    public function getLine()
    {
        if ($this->target === Target::TARGET_PROPERTY) {
            return $this->declaringClass->getStartLine();
        } elseif ($this->target === Target::TARGET_METHOD) {
            return $this->method->getStartLine();
        } else {
            return $this->class->getStartLine();
        }
    }

    public function getName()
    {
        if ($this->target === Target::TARGET_PROPERTY) {
            return $this->class->getName().'->'.$this->property->getName();
        } elseif ($this->target === Target::TARGET_METHOD) {
            return $this->class->getName().'::'.$this->method->getName();
        } else {
            return $this->class->getName();
        }
    }
}
