<?php
namespace kuiper\annotations;

use ReflectionClass;
use ReflectionProperty;
use kuiper\annotations\exception\AnnotationException;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\annotations\annotation\Target;
use kuiper\reflection\VarType;
use RuntimeException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AnnotationReader extends AbstractReader
{
    const ERRMODE_SILENT = 1;
    const ERRMODE_WARNING = 2;
    const ERRMODE_EXCEPTION = 3;

    /**
     * Cached annotations
     *
     * @var array
     */
    protected $annotations = [];

    /**
     * Cached annotation metadata
     *
     * @var array
     */
    protected $annotationMetadata = [];

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @var DocReader
     */
    protected $docReader;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array excluded (value = true) or included (value = false) names
     */
    protected $ignoredNames = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var int
     */
    protected $errorMode = self::ERRMODE_WARNING;

    /**
     * A list with annotations that are not causing exceptions when not resolved to an annotation class.
     *
     * The names are case sensitive.
     *
     * @var array
     */
    private static $IGNORED_NAMES = [
        // Annotation tags
        'Annotation' => true,
        'Target' => true,
    ];

    public function __construct(
        ParserInterface $parser = null,
        EventDispatcherInterface $eventDispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->parser = $parser ?: new Parser();
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->docReader = new DocReader();
    }

    /**
     * Adds the annotation names
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
     * Ignores these annotation names
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
     * Clears internal cache
     */
    public function clearCache()
    {
        $this->annotations = [];
        $this->annotationMetadata = [];
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
     * Sets the logger
     *
     * @param LoggerInterface $logger 
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Sets error model
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
     * @inheritDoc
     */
    public function getAnnotations(ReflectionClass $class)
    {
        $className = $class->getName();
        if (isset($this->annotations[$className])) {
            return $this->annotations[$className];
        }
        if ($this->eventDispatcher) {
            $event = new AnnotationEvent($class);
            $this->eventDispatcher->dispatch(AnnotationEvents::PRE_PARSE, $event);
            $annotations = $event->getAnnotations();
            if (is_array($annotations)) {
                $this->annotations[$className] = $annotations;
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
        isset($this->logger) && $this->logger->debug(
            "[AnnotationReader] parse annotations from " . $class->getName()
        );
        $context = new AnnotationContext($class);
        $annotations = $this->parser->parse($class);
        if (!empty($annotations['class'])) {
            foreach ($annotations['class'] as $annotation) {
                $this->addAnnotation($annotation, $context);
            }
        }
        if (!empty($annotations['methods'])) {
            foreach ($annotations['methods'] as $name => $methodAnnotations) {
                $method = $class->getMethod($name);
                $context->setMethod($method);
                foreach ($methodAnnotations as $annotation) {
                    $this->addAnnotation($annotation, $context);
                }
            }
        }
        if (!empty($annotations['properties'])) {
            foreach ($annotations['properties'] as $name => $propertyAnnotations) {
                $property = $class->getProperty($name);
                $context->setProperty($property);
                foreach ($propertyAnnotations as $annotation) {
                    $this->addAnnotation($annotation, $context);
                }
            }
        }
        return [
            'class' => $context->getClassAnnotations(),
            'methods' => $context->getMethodAnnotations(),
            'properties' => $context->getPropertyAnnotations()
        ];
    }

    protected function addAnnotation($annotation, $context)
    {
        $annotationObj = $this->createAnnotation($annotation, $context, $context->getTarget(), true);
        if ($annotationObj !== null) {
            $context->add($annotationObj);
        }
    }

    /**
     * @param Annotation $annotation
     * @param int $target
     * @param array $context
     *  - declaringClass ReflectionClass annotation declaring class
     *  - class ReflectionClass current class
     *  - method ReflectionMethod
     *  - property ReflectionProperty
     *  - name string describe context
     *  - file string
     *  - line string
     *  - annotation string annotation name
     *  - annotationClass string annotation class
     */
    protected function createAnnotation($annotation, $context, $target, $ignoredNotFound = false)
    {
        $annotationName = $annotation->getName();
        if ($this->shouldIgnore($annotationName)) {
            return;
        }
        $context->setAnnotation($annotation);
        $annotationClass = $context->resolveClassName($annotationName);
        if (!class_exists($annotationClass)) {
            if ($ignoredNotFound) {
                $this->handleNotFound($context, $annotationClass);
                return;
            } else {
                throw new AnnotationException(sprintf(
                    "Cannot load annotation @%s which resolve to %s. %s",
                    $annotationName,
                    $annotationClass,
                    $this->describeAnnotation($context)
                ));
            }
        }
        $context->setAnnotationClass($annotationClass);
        $metadata = $this->getAnnotationMetadata($annotationClass);
        if ($metadata['is_annotation'] === false) {
            throw new AnnotationException(sprintf(
                "The class '%s' is not annotated with @Annotation. %s",
                $annotationClass,
                $this->describeAnnotation($context)
            ));
        }
        if (0 === ($metadata['targets'] & $target)) {
            throw new AnnotationException(sprintf(
                "Annotation @%s is not allowed here. ".
                "You may only use this annotation on these code elements: %s. %s",
                $annotationName,
                Target::describe($metadata['targets']),
                $this->describeAnnotation($context, false)
            ));
        }
        $values = $this->getArguments($annotation, $context);
        if (isset($values['value'])
            && isset($metadata['default_property'])
            && $metadata['default_property'] != 'value') {
            $values[$metadata['default_property']] = $values['value'];
            unset($values['value']);
        }
        if (!empty($metadata['attribute_types'])) {
            $values = $this->validateValues($values, $metadata['attribute_types'], $context);
        }
        if ($metadata['has_constructor']) {
            try {
                $annotationObj = new $annotationClass($values, $context);
            } catch (InvalidArgumentException $e) {
                throw new AnnotationException($e->getMessage() . $this->describeAnnotation($context));
            }
        } else {
            $annotationObj = new $annotationClass;
            if (!empty($values)) {
                $this->assignValues($annotationObj, $values, $metadata['properties'], $context);
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

    protected function handleNotFound($context, $annotationClass)
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
                    error_log($message);
                }
            } elseif ($this->errorMode === self::ERRMODE_EXCEPTION) {
                throw new AnnotationException($message);
            } else {
                throw new RuntimeException("Unknown error mode {$this->errorMode}");
            }
        }
    }

    /**
     * @param string $annotationClass
     * @return array
     *  - is_annotation boolean whether the class is annotation
     *  - targets int bitmask of targets
     *  - has_constructor boolean whether should call the class constructor
     *  - properties array annotation properties
     *  - default_property string name of default property
     *  - attribute_types array
     */
    protected function getAnnotationMetadata($className)
    {
        if (isset($this->annotationMetadata[$className])) {
            return $this->annotationMetadata[$className];
        }
        $class = new ReflectionClass($className);
        $metadata = ['is_annotation' => false];
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
            if (!isset($metadata['targets'])) {
                $metadata['targets'] = Target::TARGET_ALL;
            }
            $constructor = $class->getConstructor();
            $metadata['has_constructor'] = ($constructor !== null
                                            && $constructor->getNumberOfParameters() > 0);
            $metadata = array_merge($metadata, $this->parsePropertyMetadata($class));
            $this->addPropertyAnnotationMetadata($class, $metadata, $annotations);
        }
        return $this->annotationMetadata[$className] = $metadata;
    }

    protected function parsePropertyMetadata(ReflectionClass $class)
    {
        $metadata = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $name = $property->getName();
            $metadata['properties'][$name] = true;
            if (!isset($metadata['default_property'])) {
                $metadata['default_property'] = $name;
            }
            try {
                $metadata['attribute_types'][$name] = [
                    'required' => false,
                    'type' => $this->docReader->getPropertyType($property)
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

    protected function addPropertyAnnotationMetadata($class, &$metadata, $annotations)
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
                    $metadata['attribute_types'][$property]['type'] = 'enum';
                    $enums = $annotation->getArguments();
                    if (empty($enums['value'])) {
                        $enums = array_keys($constants);
                    } else {
                        $enums = $enums['value'];
                        foreach ($enums as $enum) {
                            if (!isset($constants[$enum])) {
                                throw new AnnotationException(sprintf(
                                    "Unknown enum name '%s' on property %s->%s annotation @Enum at '%s' line %d",
                                    $enum,
                                    $class->getName(),
                                    $property,
                                    $annotation['file'],
                                    $annotation['line']
                                ));
                            }
                        }
                    }
                    $metadata['attribute_types'][$property]['enums'] = $enums;
                }
            }
        }
    }

    protected function getArguments($annotation, $context)
    {
        $values = [];
        $arguments = $annotation->getArguments();
        if (!is_array($arguments)) {
            return $values;
        }
        foreach ($arguments as $name => $value) {
            if ($value instanceof Annotation) {
                $obj = $this->createAnnotation($value, $context, Target::TARGET_ANNOTATION);
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
                        $obj = $this->createAnnotation($item, $context, Target::TARGET_ANNOTATION);
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

    protected function validateValues(&$values, $types, $context)
    {
        foreach ($types as $property => $type) {
            if (!isset($values[$property])) {
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
            $value = $values[$property];
            if ($type['type'] === 'enum') {
                $value = $this->getEnumValue($property, $value, $type['enums'], $context);
            } elseif ($type['type'] instanceof VarType && !$type['type']->validate($value)) {
                throw new AnnotationException(sprintf(
                    "Attribute '%s' expects %s, got '%s'. %s",
                    $property,
                    $type['type'],
                    VarType::describe($value),
                    $this->describeAnnotation($context)
                ));
            }
            $values[$property] = $value;
        }
        return $values;
    }

    protected function getEnumValue($property, $value, $enums, $context)
    {
        if (!is_string($value)) {
            throw new AnnotationException(sprintf(
                "Attribute '%s' should be string, got '%s'. %s",
                $property,
                VarType::describe($value),
                $this->describeAnnotation($context)
            ));
        }
        $value = strtoupper($value);
        $constName = $context->getAnnotationClass() . '::' . $value;
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
     * @param array $values
     * @param array $properties
     * @param array $context
     */
    protected function assignValues($annotationObj, $values, $properties, $context)
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
