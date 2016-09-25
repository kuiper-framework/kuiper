<?php
namespace kuiper\annotations;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use kuiper\annotations\annotation\Target;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\reflection\ReflectionFile;

class AnnotationContext
{
    /**
     * @var array
     */
    private $classAnnotations = [];

    /**
     * @var array
     */
    private $methodAnnotations = [];

    /**
     * @var array
     */
    private $propertyAnnotations = [];

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
     * @var ReflectionFile
     */
    private $file;

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
    private $annotationClass;

    public function __construct(ReflectionClass $class)
    {
        $this->class = $class;
        $this->declaringClass = $class;
        $this->target = Target::TARGET_CLASS;
        $this->file = new ReflectionFile($class->getFileName());
    }

    public function setMethod(ReflectionMethod $method)
    {
        $this->method = $method;
        $this->declaringClass = $method->getDeclaringClass();
        $this->target = Target::TARGET_METHOD;
        $this->file = new ReflectionFile($this->declaringClass->getFileName());
    }

    public function setProperty(ReflectionProperty $property)
    {
        $this->property = $property;
        $this->declaringClass = $property->getDeclaringClass();
        $this->target = Target::TARGET_PROPERTY;
        $this->file = new ReflectionFile($this->declaringClass->getFileName());
    }

    public function setAnnotation(Annotation $annotation)
    {
        $this->annotation = $annotation;
    }

    public function setAnnotationClass($annotationClass)
    {
        $this->annotationClass = $annotationClass;
    }

    public function getClassAnnotations()
    {
        return $this->classAnnotations;
    }

    public function getMethodAnnotations()
    {
        return $this->methodAnnotations;
    }

    public function getPropertyAnnotations()
    {
        return $this->propertyAnnotations;
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

    public function getAnnotationClass()
    {
        return $this->annotationClass;
    }

    public function getFile()
    {
        return $this->file->getFile();
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
            return $this->class->getName() . '->' . $this->property->getName();
        } elseif ($this->target === Target::TARGET_METHOD) {
            return $this->class->getName() . '::' . $this->method->getName();
        } else {
            return $this->class->getName();
        }
    }

    public function add($annotationObj)
    {
        if ($this->target === Target::TARGET_CLASS) {
            $this->classAnnotations[] = $annotationObj;
        } elseif ($this->target === Target::TARGET_METHOD) {
            $this->methodAnnotations[$this->method->getName()][] = $annotationObj;
        } elseif ($this->target === Target::TARGET_PROPERTY) {
            $this->propertyAnnotations[$this->property->getName()][] = $annotationObj;
        }
    }

    public function resolveClassName($name, $mustExists = false)
    {
        $className = $this->file->resolveClassName($name, $this->declaringClass->getNamespaceName());
        if ($mustExists && !class_exists($className) && !interface_exists($className)) {
            throw new ClassNotFoundException(sprintf(
                "Class '%s' which resolved from '%s' in '%s' does not exist",
                $className,
                $name,
                $this->getFile()
            ));
        }
        return $className;
    }
}
