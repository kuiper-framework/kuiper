<?php

namespace kuiper\di\definition\decorator;

use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\exception\AnnotationException as AnnotationParseException;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\annotations\ReaderInterface;
use kuiper\di\annotation\Autowired;
use kuiper\di\annotation\Inject;
use kuiper\di\annotation\Injectable;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\NamedParameters;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\DefinitionEntry;
use kuiper\di\exception\AnnotationException;
use ReflectionClass;
use ReflectionMethod;

class AutowireDecorator extends DefinitionDecorator
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var DocReaderInterface
     */
    private $docReader;

    public function __construct(ReaderInterface $reader, DocReaderInterface $docReader)
    {
        $this->annotationReader = $reader;
        $this->docReader = $docReader;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        if ($definition instanceof ObjectDefinition) {
            $className = $definition->getClassName() ?: $entry->getName();
            $class = new ReflectionClass($className);
            try {
                $this->readAnnotations($class, $definition);

                return $entry;
            } catch (AnnotationParseException $e) {
                throw new AnnotationException($e->getMessage());
            }
        } else {
            return parent::decorate($entry);
        }
    }

    protected function readAnnotations(ReflectionClass $class, ObjectDefinition $definition)
    {
        $autowired = false;
        $classAnnotations = $this->annotationReader->getClassAnnotations($class);
        foreach ($classAnnotations as $annot) {
            if ($annot instanceof Autowired) {
                $autowired = true;
            } elseif ($annot instanceof Injectable) {
                $definition->scope($annot->scope);
                if ($annot->lazy) {
                    $definition->lazy();
                }
            }
        }
        $properties = $this->readProperties($class, $definition->getProperties());
        $methods = $this->readMethods($class, $definition->getMethods());
        if ($autowired) {
            try {
                $this->autowire($class, $properties, $methods);
            } catch (ClassNotFoundException $e) {
                $this->logger && $this->logger->warning($e->getMessage(), ['exception' => $e]);
            }
        }
        if (!empty($properties)) {
            $definition->setProperties($properties);
        }
        $params = $definition->getConstructorParameters();
        if (empty($params) || $params instanceof NamedParameters) {
            $constructorParams = [];
            if (isset($methods['__construct'])) {
                $constructorParams = $methods['__construct'][0];
                unset($methods['__construct']);
            }
            if ($params) {
                $constructorParams = array_merge($constructorParams, $params->getParameters());
            }
            $constructorParams = $this->getConstructorParameters($class->getName(), $constructorParams);
            $definition->setConstructorParameters($constructorParams);
        }
        if (!empty($methods)) {
            $definition->setMethods($methods);
        }
    }

    protected function readProperties(ReflectionClass $class, array $exists)
    {
        $definitions = [];
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic() || array_key_exists($property->getName(), $exists)) {
                continue;
            }

            $injectAnnot = $this->annotationReader->getPropertyAnnotation($property, Inject::class);
            if ($injectAnnot === null) {
                continue;
            }
            if (($entryName = $injectAnnot->getName()) === null) {
                $entryName = $this->docReader->getPropertyClass($property);
                if ($entryName === null) {
                    throw new AnnotationException(sprintf(
                        '@Inject found on property %s->%s but unable to guess what to inject, use a @var annotation',
                        $class->getName(),
                        $property->getName()
                    ));
                }
            }
            $definitions[$property->getName()] = new AliasDefinition($entryName);
        }

        return $definitions;
    }

    protected function readMethods(ReflectionClass $class, array $exists)
    {
        $definitions = [];
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || array_key_exists($method->getName(), $exists)) {
                continue;
            }
            $injectAnnot = $this->annotationReader->getMethodAnnotation($method, Inject::class);
            if ($injectAnnot === null) {
                continue;
            }
            $params = [];
            $injectParams = $injectAnnot->getParameters();
            $docParams = [];
            if (empty($injectParams)) {
                $docParams = $this->getParameterClasses($method);
            }
            foreach ($method->getParameters() as $index => $parameter) {
                $name = $parameter->getName();
                $type = null;
                if (isset($injectParams[$index])) {
                    $type = $injectParams[$index];
                } elseif (isset($injectParams[$name])) {
                    $type = $injectParams[$name];
                } elseif (isset($docParams[$index])) {
                    $type = $docParams[$index];
                } elseif (!$parameter->isOptional()) {
                    throw new AnnotationException(sprintf(
                        '@Inject found on method %s::%s but unable to guess parameter'
                        ." '%s' type, use a @param annotation",
                        $class->getName(),
                        $method->getName(),
                        $name
                    ));
                }
                if ($type !== null) {
                    $params[$index] = new AliasDefinition($type);
                }
            }
            $definitions[strtolower($method->getName())][] = $params;
        }

        return $definitions;
    }

    protected function autowire($class, &$properties, &$methods)
    {
        $definitions = [];
        $lowerProperties = array_map('strtolower', array_keys($properties));
        $lowerMethods = array_map('strtolower', array_keys($methods));
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic()) {
                continue;
            }
            $name = strtolower($method->getName());
            if (in_array($name, $lowerMethods)) {
                continue;
            }
            // autowire setter
            if (strpos($name, 'set') !== 0) {
                continue;
            }
            $property = substr($name, 3);
            if (in_array($property, $lowerProperties)) {
                // @Inject property found
                continue;
            }
            $paramTypes = $this->getParameterClasses($method);
            if ($paramTypes === null) {
                // cannot resolve some parameter type
                $this->logger && $this->logger->warning(sprintf(
                    "Cannot resolve method '%s::%s' parameters",
                    $class->getName(),
                    $method->getName()
                ));
                continue;
            }
            foreach ($paramTypes as $i => $type) {
                $paramTypes[$i] = new AliasDefinition($type);
            }
            $methods[$name][] = $paramTypes;
        }
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if (isset($properties[$property->getName()])) {
                continue;
            }
            $setter = 'set'.strtolower($property->getName());
            if (isset($methods[$setter])) {
                continue;
            }
            $type = $this->docReader->getPropertyClass($property);
            if ($type !== null) {
                $properties[$property->getName()] = new AliasDefinition($type);
            }
        }
    }

    protected function getParameterClasses(ReflectionMethod $method)
    {
        $docParams = $this->docReader->getParameterClasses($method);
        $paramTypes = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isOptional()) {
                continue;
            }
            if (($class = $parameter->getClass()) !== null) {
                $type = $class->getName();
            } elseif (isset($docParams[$parameter->getName()])) {
                $type = $docParams[$parameter->getName()];
            } else {
                return null;
            }
            $paramTypes[] = $type;
        }

        return $paramTypes;
    }

    public function getReader()
    {
        return $this->reader;
    }

    public function getDocReader()
    {
        return $this->docReader;
    }
}
