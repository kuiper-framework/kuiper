<?php

namespace kuiper\annotations;

use kuiper\annotations\annotation\Target;
use kuiper\annotations\exception\AnnotationException;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\TypeUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AnnotationReader extends AbstractReader implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const ERRMODE_SILENT = 1;
    const ERRMODE_WARNING = 2;
    const ERRMODE_EXCEPTION = 3;

    /**
     * Cached annotations.
     *
     * @var array
     */
    protected $annotations = [];

    /**
     * @var AnnotationMetadataFactory
     */
    protected $annotationMetadataFactory;

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var DocReaderInterface
     */
    protected $docReader;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ReflectionFileFactoryInterface
     */
    protected $reflectionFileFactory;

    /**
     * @var array excluded (value = true) or included (value = false) names
     */
    protected $ignoredNames = [
        'SuppressWarnings' => true,
    ];

    /**
     * @var int
     */
    protected $errorMode = self::ERRMODE_WARNING;

    public function __construct(
        ReflectionFileFactoryInterface $reflectionFileFactory = null,
        ParserInterface $parser = null,
        DocReaderInterface $docReader = null
    ) {
        $this->reflectionFileFactory = $reflectionFileFactory ?: ReflectionFileFactory::createInstance();
        $this->parser = $parser ?: new Parser($this->reflectionFileFactory);
        $this->docReader = $docReader ?: new DocReader($this->reflectionFileFactory);
        $this->annotationMetadataFactory = new AnnotationMetadataFactory($this->parser, $this->docReader);
    }

    /**
     * Adds the annotation names.
     *
     * @param string[] $names
     *
     * @return $this
     */
    public function includeNames(array $names)
    {
        $this->ignoredNames = array_merge(
            $this->ignoredNames,
            array_combine($names, array_fill(0, count($names), false))
        );

        return $this;
    }

    /**
     * Ignores these annotation names.
     *
     * @param string[] $names
     *
     * @return $this
     */
    public function ignoreNames(array $names)
    {
        $this->ignoredNames = array_merge(
            $this->ignoredNames,
            array_combine($names, array_fill(0, count($names), true))
        );

        return $this;
    }

    /**
     * Clears internal cache.
     *
     * @param string|null $className
     *
     * @return $this
     */
    public function clearCache(string $className = null)
    {
        if (isset($className)) {
            unset($this->annotations[$className]);
            $this->annotationMetadataFactory->clearCache($className);
        } else {
            $this->annotations = [];
            $this->annotationMetadataFactory->clearCache();
        }

        return $this;
    }

    /**
     * @param ParserInterface $parser
     *
     * @return $this
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param ReflectionFileFactoryInterface $reflectionFileFactory
     *
     * @return $this
     */
    public function setReflectionFileFactory(ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->reflectionFileFactory = $reflectionFileFactory;

        return $this;
    }

    /**
     * Sets error model.
     *
     * @param int $mode
     *
     * @return $this
     */
    public function setErrorMode($mode)
    {
        if (!in_array($mode, [self::ERRMODE_EXCEPTION, self::ERRMODE_SILENT, self::ERRMODE_WARNING])) {
            throw new \InvalidArgumentException("Invalid error mode '{$mode}'");
        }
        $this->errorMode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations(\ReflectionClass $class)
    {
        $className = $class->getName();
        if ($this->isCacheHit($className)) {
            return $this->getCached($className);
        }
        if ($this->eventDispatcher) {
            $event = new AnnotationEvent($class);
            $this->eventDispatcher->dispatch(AnnotationEvents::PRE_PARSE, $event);
            $annotations = $event->getAnnotations();
            if ($annotations) {
                return $this->save($className, $annotations);
            }
        }
        $annotations = $this->parseAnnotations($class);
        if ($this->eventDispatcher) {
            $event = new AnnotationEvent($class);
            $event->setAnnotations($annotations);
            $this->eventDispatcher->dispatch(AnnotationEvents::POST_PARSE, $event);
            $annotations = $event->getAnnotations();
        }

        return $this->save($className, $annotations);
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return AnnotationSink
     */
    protected function parseAnnotations(\ReflectionClass $class)
    {
        if (!$this->isClassFileExists($class)) {
            return AnnotationSink::emptySink();
        }
        $this->debug('parse annotations from '.$class->getName());
        $annotations = $this->parser->parse($class);
        $classAnnotations = $this->parseClassAnnotations($class, $annotations);
        $methodAnnotations = $this->parseMethodAnnotations($class, $annotations);
        $propertyAnnotations = $this->parsePropertyAnnotations($class, $annotations);

        return new AnnotationSink($classAnnotations, $methodAnnotations, $propertyAnnotations);
    }

    private function isClassFileExists(\ReflectionClass $class)
    {
        $file = $class->getFileName();
        // If the class is defined in the PHP core or in a PHP extension, FALSE is returned.
        return $file && false === strpos($file, "eval()'d code");
    }

    private function parseClassAnnotations(\ReflectionClass $class, AnnotationSink $annotations)
    {
        $classAnnotations = [];
        foreach ($annotations->getClassAnnotations() as $annotation) {
            /** @var Annotation $annotation */
            if ($this->shouldIgnore($annotation->getName())) {
                continue;
            }
            try {
                $classAnnotations[] = $this->createAnnotation($annotation, $class, Target::TARGET_CLASS);
            } catch (\Exception $e) {
                $this->handleError($e, $annotation, $class);
            }
        }

        return $classAnnotations;
    }

    private function parseMethodAnnotations(\ReflectionClass $class, AnnotationSink $annotations)
    {
        $methodAnnotations = [];
        foreach ($class->getMethods() as $method) {
            foreach ($annotations->getMethodAnnotations($method->getName()) as $annotation) {
                /** @var Annotation $annotation */
                if ($this->shouldIgnore($annotation->getName())) {
                    continue;
                }
                try {
                    $methodAnnotations[$method->getName()][] = $this->createAnnotation($annotation, $method, Target::TARGET_METHOD);
                } catch (\Exception $e) {
                    $this->handleError($e, $annotation, $method);
                }
            }
        }

        return $methodAnnotations;
    }

    private function parsePropertyAnnotations(\ReflectionClass $class, AnnotationSink $annotations)
    {
        $propertyAnnotations = [];
        $traits = $class->getTraits();
        foreach ($class->getProperties() as $property) {
            foreach ($annotations->getPropertyAnnotations($property->getName()) as $annotation) {
                /** @var Annotation $annotation */
                if ($this->shouldIgnore($annotation->getName())) {
                    continue;
                }
                try {
                    foreach ($traits as $trait) {
                        // 使用 trait 属性代替
                        if ($trait->hasProperty($property->getName())) {
                            $property = $trait->getProperty($property->getName());
                            break;
                        }
                    }
                    $propertyAnnotations[$property->getName()][] = $this->createAnnotation($annotation, $property, Target::TARGET_PROPERTY);
                } catch (\Exception $e) {
                    $this->handleError($e, $annotation, $property);
                }
            }
        }

        return $propertyAnnotations;
    }

    /**
     * @param Annotation $annotation
     * @param \Reflector $reflector
     * @param int        $target
     *
     * @return object
     */
    protected function createAnnotation(Annotation $annotation, $reflector, $target)
    {
        $annotationClass = $this->resolveAnnotationClass($annotation, $reflector);
        $metadata = $this->getAnnotationMetadata($annotationClass);
        if (0 === ($metadata->getTargets() & $target)) {
            throw new AnnotationException(sprintf(
                'Annotation @%s is not allowed here. '.
                'You may only use this annotation on these code elements: %s.',
                $annotation->getName(), Target::describe($metadata->getTargets())
            ));
        }

        $values = $this->getArguments($annotation, $reflector, $metadata);
        if ($metadata->hasConstructor()) {
            return new $annotationClass($values, $reflector);
        } else {
            $annotationObj = new $annotationClass();
            foreach ($values as $name => $value) {
                if (!$metadata->hasProperty($name)) {
                    throw new AnnotationException(sprintf(
                        "Property '%s' does not exist. Available properties %s.",
                        $name, json_encode($metadata->getProperties())
                    ));
                }
                $annotationObj->{$name} = $value;
            }
        }

        return $annotationObj;
    }

    /**
     * Ignore annotation in the ignoredNames or first letter is lower case.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function shouldIgnore(string $name)
    {
        if (empty($name)) {
            return false;
        }
        if (array_key_exists($name, $this->ignoredNames)) {
            return $this->ignoredNames[$name];
        }

        return !ctype_upper($name[0]);
    }

    /**
     * @param string $className the annotation class name
     *
     * @return AnnotationMetadata
     */
    protected function getAnnotationMetadata(string $className)
    {
        $metadata = $this->annotationMetadataFactory->create($className);
        if (false === $metadata) {
            throw new AnnotationException("The class '{$className}' is not annotated with @Annotation.");
        }

        return $metadata;
    }

    protected function getArguments(Annotation $annotation, $reflector, AnnotationMetadata $metadata)
    {
        $values = [];
        foreach ($annotation->getArguments() as $name => $value) {
            if ($value instanceof Annotation) {
                $value = $this->createAnnotation($value, $reflector, Target::TARGET_ANNOTATION);
            } elseif (is_array($value)) {
                foreach ($value as $i => $item) {
                    if ($item instanceof Annotation) {
                        $value[$i] = $this->createAnnotation($item, $reflector, Target::TARGET_ANNOTATION);
                    }
                }
            }
            $values[$name] = $value;
        }

        if (isset($values['value'])
            && null !== $metadata->getDefaultProperty()
            && 'value' != $metadata->getDefaultProperty()) {
            // update default_property value
            $values[$metadata->getDefaultProperty()] = $values['value'];
            unset($values['value']);
        }
        foreach ($metadata->getProperties() as $name) {
            if ($metadata->getPropertyAttribute($name, 'required') && !isset($values[$name])) {
                throw new AnnotationException(sprintf(
                    "Attribute '%s' of Annotation @%s should not be empty.",
                    $name, $annotation->getName()
                ));
            }
            if (isset($values[$name])) {
                $enums = $metadata->getPropertyAttribute($name, 'enums');
                if (!empty($enums)) {
                    $values[$name] = $this->getEnumValue($metadata, $name, $values[$name]);
                } elseif (!TypeUtils::validate($metadata->getPropertyAttribute($name, 'type'), $values[$name])) {
                    throw new AnnotationException(sprintf(
                        "Attribute '%s' expects %s, got %s. ",
                        $name, $metadata->getPropertyAttribute($name, 'type'), gettype($values[$name])
                    ));
                }
            }
        }

        return $values;
    }

    private function resolveAnnotationClass(Annotation $annotation, $reflector)
    {
        if ($reflector instanceof \ReflectionProperty) {
            $file = $reflector->getDeclaringClass()->getFileName();
            $namespace = $reflector->getDeclaringClass()->getNamespaceName();
        } elseif ($reflector instanceof \ReflectionMethod) {
            $file = $reflector->getFileName();
            $namespace = $reflector->getDeclaringClass()->getNamespaceName();
        } else {
            /** @var \ReflectionClass $reflector */
            $file = $reflector->getFileName();
            $namespace = $reflector->getNamespaceName();
        }
        $resolver = new FqcnResolver($this->reflectionFileFactory->create($file));
        $annotationClass = $resolver->resolve($annotation->getName(), $namespace);
        if (!class_exists($annotationClass)) {
            throw new ClassNotFoundException(sprintf(
                'Cannot load annotation @%s which resolve to %s.',
                $annotation->getName(), $annotationClass
            ));
        }

        return $annotationClass;
    }

    private function getEnumValue(AnnotationMetadata $metadata, string $property, $value)
    {
        if (!is_string($value)) {
            throw new AnnotationException(sprintf(
                "Attribute '%s' should be string, got '%s'.",
                $property, gettype($value)
            ));
        }
        $value = strtoupper($value);
        $constName = $metadata->getClassName().'::'.$value;
        if (!defined($constName)) {
            throw new AnnotationException(sprintf(
                "Constant '%s' is not defined for attribute '%s'.",
                $constName, $property
            ));
        }
        if (!in_array($value, $enums = $metadata->getPropertyAttribute($property, 'enums'))) {
            throw new AnnotationException(sprintf(
                "Enum '%s' is invalid for attribute '%s', available: %s.",
                $value, $property, json_encode($enums)
            ));
        }

        return constant($constName);
    }

    private function isCacheHit(string $className)
    {
        return isset($this->annotations[$className]);
    }

    private function getCached(string $className)
    {
        return $this->annotations[$className];
    }

    private function save(string $className, AnnotationSink $annotations)
    {
        return $this->annotations[$className] = $annotations;
    }

    /**
     * @param string $message
     */
    private function debug(string $message)
    {
        $this->logger && $this->logger->debug('[AnnotationReader] '.$message);
    }

    /**
     * @param \Exception $e
     * @param Annotation $annotation
     * @param \Reflector $reflector
     */
    private function handleError(\Exception $e, Annotation $annotation, $reflector)
    {
        if (self::ERRMODE_SILENT === $this->errorMode) {
            return;
        }
        if ($reflector instanceof \ReflectionProperty) {
            $reflectorType = 'property';
            $reflectorName = $reflector->getDeclaringClass()->getName().'->'.$reflector->getName();
            $file = $reflector->getDeclaringClass()->getFileName();
            $line = null;
        } elseif ($reflector instanceof \ReflectionMethod) {
            $reflectorType = 'method';
            $reflectorName = $reflector->getDeclaringClass()->getName().'->'.$reflector->getName();
            $file = $reflector->getFileName();
            $line = $reflector->getStartLine();
        } else {
            $reflectorType = 'class';
            /** @var \ReflectionClass $reflector */
            $reflectorName = $reflector->getName();
            $file = $reflector->getFileName();
            $line = $reflector->getStartLine();
        }
        $message = sprintf(
            '%s Error occur on Annotation @%s on %s %s in %s',
            $e->getMessage(),
            $annotation->getName(),
            $reflectorType, $reflectorName, $file
        );
        if ($line) {
            $message .= " on line $line";
        }

        if (self::ERRMODE_WARNING === $this->errorMode) {
            if (isset($this->logger)) {
                $this->logger->warning($message);
            } else {
                trigger_error($message);
            }
        } else {
            throw new AnnotationException($message, 0, $e);
        }
    }
}
