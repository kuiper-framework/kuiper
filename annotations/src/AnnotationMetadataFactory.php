<?php

namespace kuiper\annotations;

use kuiper\annotations\annotation\Target;
use kuiper\annotations\exception\AnnotationException;
use kuiper\annotations\exception\ClassNotFoundException;

class AnnotationMetadataFactory
{
    /**
     * @var ParserInterface
     */
    private $parser;
    /**
     * @var DocReaderInterface
     */
    private $docReader;
    /**
     * @var array
     */
    private $cache;

    public function __construct(ParserInterface $parser, DocReaderInterface $docReader)
    {
        $this->parser = $parser;
        $this->docReader = $docReader;
    }

    /**
     * @param string $className
     *
     * @return AnnotationMetadata return false if $className is NOT Annotation class
     *
     * @throws ClassNotFoundException
     * @throws AnnotationException
     */
    public function create(string $className): AnnotationMetadata
    {
        if ($this->isCacheHit($className)) {
            return $this->getCached($className);
        }
        $class = new \ReflectionClass($className);
        $annotations = $this->parser->parse($class);
        if (!$this->isAnnotation($annotations)) {
            return $this->save($className, false);
        }
        $metadata = new AnnotationMetadata($className);

        foreach ($annotations->getClassAnnotations() as $annotation) {
            /** @var Annotation $annotation */
            if ('Target' === $annotation->getName()) {
                $target = new Target($annotation->getArguments());
                $metadata->setTargets($target->targets);
            }
        }

        $constructor = $class->getConstructor();
        if (null !== $constructor && $constructor->getNumberOfParameters() > 0) {
            $metadata->setHasConstructor(true);
        }
        $this->parsePropertyTypes($metadata, $class);
        $this->parseAnnotationAnnotations($metadata, $class, $annotations);

        return $this->save($className, $metadata);
    }

    public function clearCache(string $className = null)
    {
        if (isset($className)) {
            unset($this->cache[$className]);
        } else {
            $this->cache = [];
        }
    }

    private function isCacheHit(string $className)
    {
        return isset($this->cache[$className]);
    }

    private function getCached(string $className)
    {
        return $this->cache[$className];
    }

    private function save(string $className, $metadata)
    {
        return $this->cache[$className] = $metadata;
    }

    private function isAnnotation(AnnotationSink $annotations)
    {
        foreach ($annotations->getClassAnnotations() as $annotation) {
            /** @var Annotation $annotation */
            if ('Annotation' === $annotation->getName()) {
                return true;
            }
        }

        return false;
    }

    private function parsePropertyTypes(AnnotationMetadata $metadata, \ReflectionClass $class)
    {
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                // ignore static properties
                continue;
            }
            if (null === $metadata->getDefaultProperty()) {
                $metadata->setDefaultProperty($property->getName());
            }
            $metadata->setPropertyAttribute($property->getName(), 'required', false);
            $metadata->setPropertyAttribute($property->getName(), 'type', $this->docReader->getPropertyType($property));
        }
    }

    protected function parseAnnotationAnnotations(AnnotationMetadata $metadata, \ReflectionClass $class, AnnotationSink $annotations)
    {
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                // ignore static properties
                continue;
            }
            foreach ($annotations->getPropertyAnnotations($property->getName()) as $annotation) {
                /** @var Annotation $annotation */
                $name = $annotation->getName();
                if ('Required' === $name) {
                    $metadata->setPropertyAttribute($property->getName(), 'required', true);
                } elseif ('Default' === $name) {
                    $metadata->setDefaultProperty($property->getName());
                } elseif ('Enum' === $name) {
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
                                    $enum, $class->getName(), $property, $class->getFileName()
                                ));
                            }
                        }
                    }
                    $metadata->setPropertyAttribute($property->getName(), 'enums', $enums);
                }
            }
        }
    }
}
