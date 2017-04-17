<?php

namespace kuiper\annotations;

use InvalidArgumentException;
use kuiper\annotations\annotation\Target;
use kuiper\annotations\exception\AnnotationException;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
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
     * Cached annotation metadata.
     *
     * @var array
     */
    protected $annotationMetadata = [];

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
    protected $ignoredNames = [];

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
    }

    /**
     * Adds the annotation names.
     *
     * @param array<string> $names
     */
    public function includeNames($names)
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
     * @param array<string> $names
     */
    public function ignoreNames($names)
    {
        $this->ignoredNames = array_merge(
            $this->ignoredNames,
            array_combine($names, array_fill(0, count($names), true))
        );

        return $this;
    }

    /**
     * Clears internal cache.
     */
    public function clearCache($className = null)
    {
        if (isset($className)) {
            unset($this->annotations[$className]);
            unset($this->annotationMetadata[$className]);
        } else {
            $this->annotations = [];
            $this->annotationMetadata = [];
        }

        return $this;
    }

    /**
     * @param ParserInterface $parser
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @param ReflectionFileFactoryInterface $reflectionFileFactory
     */
    public function setReflectionFileFactory(ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->reflectionFileFactory = $reflectionFileFactory;

        return $this;
    }

    /**
     * Sets error model.
     *
     * @param int $model constants
     */
    public function setErrorMode($mode)
    {
        if (!in_array($mode, [self::ERRMODE_EXCEPTION, self::ERRMODE_SILENT, self::ERRMODE_WARNING])) {
            throw new InvalidArgumentException("invalid error mode '{$mode}'");
        }
        $this->errorMode = $mode;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnnotations(ReflectionClass $class)
    {
        $file = $class->getFileName();
        if ($file && strpos($file, "eval()'d code") !== false) {
            return ['class' => [], 'methods' => [], 'properties' => []];
        }
        $className = $class->getName();
        if (isset($this->annotations[$className])) {
            return $this->annotations[$className];
        }
        if ($this->eventDispatcher) {
            $event = new AnnotationEvent($class);
            $this->eventDispatcher->dispatch(AnnotationEvents::PRE_PARSE, $event);
            $annotations = $event->getAnnotations();
            if (is_array($annotations)) {
                return $this->annotations[$className] = $annotations;
            }
        }
        $annotations = $this->parseAnnotations($class);
        if ($this->eventDispatcher) {
            $event = new AnnotationEvent($class);
            $event->setAnnotations($annotations);
            $this->eventDispatcher->dispatch(AnnotationEvents::POST_PARSE, $event);
            $annotations = $event->getAnnotations();
        }

        return $this->annotations[$className] = $annotations;
    }

    protected function parseAnnotations(ReflectionClass $class)
    {
        $this->logger && $this->logger->debug('[AnnotationReader] parse annotations from '.$class->getName());
        $context = new AnnotationContext($class, $this->reflectionFileFactory);
        $sink = ['class' => [], 'methods' => [], 'properties' => []];
        $annotations = $this->parser->parse($class);
        if (!empty($annotations['class'])) {
            foreach ($annotations['class'] as $annotation) {
                $this->addAnnotation($context->withAnnotation($annotation), $sink);
            }
        }
        if (!empty($annotations['methods'])) {
            foreach ($annotations['methods'] as $name => $methodAnnotations) {
                $method = $class->getMethod($name);
                $methodContext = $context->withMethod($method);
                foreach ($methodAnnotations as $annotation) {
                    $this->addAnnotation($methodContext->withAnnotation($annotation), $sink);
                }
            }
        }
        if (!empty($annotations['properties'])) {
            foreach ($annotations['properties'] as $name => $propertyAnnotations) {
                $property = $class->getProperty($name);
                $propertyContext = $context->withProperty($property);
                foreach ($propertyAnnotations as $annotation) {
                    $this->addAnnotation($propertyContext->withAnnotation($annotation), $sink);
                }
            }
        }

        return $sink;
    }

    protected function addAnnotation(AnnotationContext $context, array &$sink)
    {
        $annotationObj = $this->createAnnotation($context, true);
        if ($annotationObj !== null) {
            if ($context->getTarget() === Target::TARGET_CLASS) {
                $sink['class'][] = $annotationObj;
            } elseif ($context->getTarget() === Target::TARGET_METHOD) {
                $sink['methods'][$context->getMethod()->getName()][] = $annotationObj;
            } elseif ($context->getTarget() === Target::TARGET_PROPERTY) {
                $sink['properties'][$context->getProperty()->getName()][] = $annotationObj;
            }
        }
    }

    /**
     * @param AnnotationContext $context
     * @param int               $target
     * @param bool              $ignoredNotFound
     */
    protected function createAnnotation(AnnotationContext $context, $ignoredNotFound = false)
    {
        $annotation = $context->getAnnotation();
        $annotationName = $annotation->getName();
        if ($this->shouldIgnore($annotationName)) {
            return;
        }
        $annotationClass = $context->getAnnotationClassName();
        if (!class_exists($annotationClass)) {
            if ($ignoredNotFound) {
                $this->handleNotFound($context, $annotationClass);

                return;
            } else {
                throw new AnnotationException(sprintf(
                    'Cannot load annotation @%s which resolve to %s. %s',
                    $annotationName,
                    $annotationClass,
                    $this->describeAnnotation($context)
                ));
            }
        }
        $metadata = $this->getAnnotationMetadata($annotationClass);
        if ($metadata['is_annotation'] === false) {
            throw new AnnotationException(sprintf(
                "The class '%s' is not annotated with @Annotation. %s",
                $annotationClass,
                $this->describeAnnotation($context)
            ));
        }
        $target = $context->getTarget();
        if (0 === ($metadata['targets'] & $target)) {
            throw new AnnotationException(sprintf(
                'Annotation @%s is not allowed here. '.
                'You may only use this annotation on these code elements: %s. %s',
                $annotationName,
                Target::describe($metadata['targets']),
                $this->describeAnnotation($context, false)
            ));
        }
        $values = $this->getArguments($context);
        if (isset($values['value'])
            && isset($metadata['default_property'])
            && $metadata['default_property'] != 'value') {
            // update default_property value
            $values[$metadata['default_property']] = $values['value'];
            unset($values['value']);
        }
        if (!empty($metadata['attribute_types'])) {
            $values = $this->validateArguments($values, $metadata['attribute_types'], $context);
        }
        if ($metadata['has_constructor']) {
            try {
                $annotationObj = new $annotationClass($values, $context);
            } catch (InvalidArgumentException $e) {
                throw new AnnotationException($e->getMessage().$this->describeAnnotation($context));
            }
        } else {
            $annotationObj = new $annotationClass();
            if (!empty($values)) {
                $this->setProperties($annotationObj, $values, $metadata['properties'], $context);
            }
        }

        return $annotationObj;
    }

    protected function shouldIgnore($name)
    {
        if (empty($name)) {
            return false;
        }
        if (array_key_exists($name, $this->ignoredNames)) {
            return $this->ignoredNames[$name];
        }

        return !ctype_upper($name[0]);
    }

    protected function handleNotFound(AnnotationContext $context, $annotationClass)
    {
        if ($this->errorMode !== self::ERRMODE_SILENT) {
            $message = sprintf(
                "Cannot load annotation class '%s' for %s",
                $annotationClass,
                $this->describeAnnotation($context)
            );
            if ($this->errorMode === self::ERRMODE_WARNING) {
                if (isset($this->logger)) {
                    $this->logger->warning($message);
                } else {
                    trigger_error($message);
                }
            } elseif ($this->errorMode === self::ERRMODE_EXCEPTION) {
                throw new AnnotationException($message);
            } else {
                throw new RuntimeException("Unknown error mode {$this->errorMode}");
            }
        }
    }

    /**
     * @param string $className the annotation class name
     *
     * @return array
     *               - is_annotation boolean whether the class is annotation
     *               - targets int bitmask of targets
     *               - has_constructor boolean whether should call the class constructor
     *               - properties array annotation properties
     *               - default_property string name of default property
     *               - attribute_types array with key
     *               * required boolean
     *               * type ReflectionType
     *               * enums array
     */
    protected function getAnnotationMetadata($className)
    {
        if (isset($this->annotationMetadata[$className])) {
            return $this->annotationMetadata[$className];
        }
        $class = new ReflectionClass($className);
        $metadata = [
            'is_annotation' => false,
            'has_constructor' => false,
            'targets' => Target::TARGET_ALL,
        ];
        $annotations = $this->parser->parse($class);
        if (!empty($annotations['class'])) {
            foreach ($annotations['class'] as $annotation) {
                $name = $annotation->getName();
                if ($name === 'Annotation') {
                    $metadata['is_annotation'] = true;
                } elseif ($name === 'Target') {
                    $target = new Target($annotation->getArguments());
                    $metadata['targets'] = $target->targets;
                }
            }
        }
        if ($metadata['is_annotation']) {
            $constructor = $class->getConstructor();
            if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
                $metadata['has_constructor'] = true;
            }
            $metadata = array_merge($metadata, $this->parsePropertyTypes($class));
            $this->parseAnnotationAnnotations($class, $annotations, $metadata);
        }

        return $this->annotationMetadata[$className] = $metadata;
    }

    protected function parsePropertyTypes(ReflectionClass $class)
    {
        $metadata = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                // ignore static properties
                continue;
            }
            $name = $property->getName();
            $metadata['properties'][$name] = true;
            if (!isset($metadata['default_property'])) {
                // set default_property to the first property
                $metadata['default_property'] = $name;
            }
            try {
                $metadata['attribute_types'][$name] = [
                    'required' => false,
                    'type' => $this->docReader->getPropertyType($property),
                ];
            } catch (ClassNotFoundException $e) {
                throw new AnnotationException(sprintf(
                    '%s on property %s->%s',
                    $e->getMessage(),
                    $class->getName(),
                    $name
                ));
            }
        }

        return $metadata;
    }

    protected function parseAnnotationAnnotations(ReflectionClass $class, $annotations, &$metadata)
    {
        if (empty($annotations['properties'])) {
            return;
        }
        foreach ($annotations['properties'] as $property => $propertyAnnotations) {
            foreach ($propertyAnnotations as $annotation) {
                $name = $annotation->getName();
                if ($name === 'Required') {
                    $metadata['attribute_types'][$property]['required'] = true;
                } elseif ($name === 'Default') {
                    $metadata['default_property'] = $property;
                } elseif ($name === 'Enum') {
                    $constants = $class->getConstants();
                    $enums = $annotation->getArguments();
                    if (empty($enums['value'])) {
                        $enums = array_keys($constants);
                    } else {
                        $enums = $enums['value'];
                        foreach ($enums as $enum) {
                            if (!isset($constants[$enum])) {
                                throw new AnnotationException(sprintf(
                                    "Unknown enum '%s' on property %s->%s @Enum annotation at '%s'",
                                    $enum,
                                    $class->getName(),
                                    $property,
                                    $class->getFileName()
                                ));
                            }
                        }
                    }
                    $metadata['attribute_types'][$property]['enums'] = $enums;
                }
            }
        }
    }

    protected function getArguments(AnnotationContext $context)
    {
        $values = [];
        $arguments = $context->getAnnotation()->getArguments();
        if (!is_array($arguments)) {
            return $values;
        }
        foreach ($arguments as $name => $value) {
            if ($value instanceof Annotation) {
                $obj = $this->createAnnotation($context->withAnnotation($value, Target::TARGET_ANNOTATION));
                if ($obj === null) {
                    throw new AnnotationException(sprintf(
                        "Cannot resolve annotation attribute '%s' @%s. %s",
                        $name,
                        $value->getName(),
                        $this->describeAnnotation($context)
                    ));
                }
                $value = $obj;
            } elseif (is_array($value)) {
                $arrayValues = [];
                foreach ($value as $i => $item) {
                    if ($item instanceof Annotation) {
                        $obj = $this->createAnnotation($context->withAnnotation($item, Target::TARGET_ANNOTATION));
                        if ($obj === null) {
                            throw new AnnotationException(sprintf(
                                "Cannot resolve annotation attribute '%s[%s]' @%s. %s",
                                $name,
                                $i,
                                $item->getName(),
                                $this->describeAnnotation($context)
                            ));
                        }
                        $item = $obj;
                    }
                    $arrayValues[$i] = $item;
                }
                $value = $arrayValues;
            }
            $values[$name] = $value;
        }

        return $values;
    }

    protected function validateArguments(&$arguments, $types, AnnotationContext $context)
    {
        foreach ($types as $property => $type) {
            if (!isset($arguments[$property])) {
                if ($type['required']) {
                    throw new AnnotationException(sprintf(
                        "Attribute '%s' expects %s, the value should not be empty. %s",
                        $property,
                        $type['type'],
                        $this->describeAnnotation($context)
                    ));
                }
                continue;
            }
            $value = $arguments[$property];
            if (!empty($type['enums'])) {
                $value = $this->getEnumValue($property, $value, $type['enums'], $context);
            } elseif (!$type['type']->validate($value)) {
                throw new AnnotationException(sprintf(
                    "Attribute '%s' expects %s, got '%s'. %s",
                    $property,
                    $type['type'],
                    ReflectionType::describe($value),
                    $this->describeAnnotation($context)
                ));
            }
            $arguments[$property] = $value;
        }

        return $arguments;
    }

    protected function getEnumValue($property, $value, array $enums, AnnotationContext $context)
    {
        if (!is_string($value)) {
            throw new AnnotationException(sprintf(
                "Attribute '%s' should be string, got '%s'. %s",
                $property,
                ReflectionType::describe($value),
                $this->describeAnnotation($context)
            ));
        }
        $value = strtoupper($value);
        $constName = $context->getAnnotationClassName().'::'.$value;
        if (!defined($constName)) {
            throw new AnnotationException(sprintf(
                "Constant '%s' is not defined for attribute '%s'. %s",
                $constName,
                $property,
                $this->describeAnnotation($context)
            ));
        }
        if (!in_array($value, $enums)) {
            throw new AnnotationException(sprintf(
                "Enum '%s' is invalid for attribute '%s', available: %s. %s",
                $value,
                $property,
                json_encode($enums),
                $this->describeAnnotation($context)
            ));
        }

        return constant($constName);
    }

    /**
     * @param object $annotationObj
     * @param array  $values
     * @param array  $properties
     * @param array  $context
     */
    protected function setProperties($annotationObj, $values, $properties, AnnotationContext $context)
    {
        foreach ($values as $name => $val) {
            if ($name === 0) {
                continue;
            }
            if (!isset($properties[$name])) {
                throw new AnnotationException(sprintf(
                    "Property '%s' does not exist. Available properties %s. %s",
                    $name,
                    json_encode(array_keys($properties)),
                    $this->describeAnnotation($context)
                ));
            }
            $annotationObj->{$name} = $val;
        }
    }

    protected function describeAnnotation($context, $withAnnotation = true)
    {
        $desc = ($withAnnotation ? sprintf('annotation @%s on ', $context->getAnnotation()->getName()) : 'on ');
        $target = $context->getTarget();
        if ($target === Target::TARGET_CLASS) {
            $desc .= 'class';
        } elseif ($target === Target::TARGET_PROPERTY) {
            $desc .= 'property';
        } else {
            $desc .= 'class';
        }

        return sprintf('Error occured at %s %s at %s in line %d',
                       $desc, $context->getName(), $context->getFile(), $context->getLine());
    }
}
