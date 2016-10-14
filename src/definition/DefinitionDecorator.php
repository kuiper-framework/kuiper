<?php
namespace kuiper\di\definition;

use kuiper\annotations\DocReader;
use kuiper\annotations\ReaderInterface;
use kuiper\annotations\exception\AnnotationException as AnnotationParseException;
use kuiper\annotations\exception\ClassNotFoundException;
use kuiper\di\DefinitionEntry;
use kuiper\di\exception\DefinitionException;
use kuiper\di\exception\AnnotationException;
use kuiper\di\annotation\Autowired;
use kuiper\di\annotation\Injectable;
use kuiper\di\annotation\Inject;
use Psr\Log\LoggerInterface;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;

class DefinitionDecorator implements DecoratorInterface
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var DocReader
     */
    private $docReader;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(ReaderInterface $reader = null)
    {
        $this->annotationReader = $reader;
    }

    /**
     * @inheritDoc
     */
    public function decorate(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        if ($definition instanceof FactoryDefinition) {
            return $this->resolveFactoryArguments($entry);
        } elseif ($definition instanceof ObjectDefinition) {
            return $this->resolveObject($entry);
        } else {
            return $entry;
        }
    }

    /**
     * reflection closure parameters
     */
    private function resolveFactoryArguments(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        $args = $definition->getArguments();
        if (!empty($args)) {
            return $entry;
        }
        $factory = $definition->getFactory();
        if (is_array($factory)) {
            if ($factory[0] instanceof DefinitionInterface) {
                // cannot resolve alias
                return $entry;
            }
            $class = new ReflectionClass($factory[0]);
            $function = $class->getMethod($factory[1]);
        } elseif (is_string($factory) || $factory instanceof Closure) {
            $function = new ReflectionFunction($factory);
        } else {
            throw new DefinitionException("Invalid factory for entry " . $entry->getName());
        }
        $params = [];
        foreach ($function->getParameters() as $param) {
            if ($param->isOptional()) {
                try {
                    $params[] = new ValueDefinition($param->getDefaultValue());
                } catch (\ReflectionException $e) {
                    break;
                }
            } else {
                if (($class = $param->getClass()) === null) {
                    // cannot resolve parameters
                    return $entry;
                }
                $params[] = new AliasDefinition($class->getName());
            }
        }
        $newDef = new FactoryDefinition($factory, $params);
        if ($definition->isLazy()) {
            $newDef->lazy();
        }
        return new DefinitionEntry($entry->getName(), $newDef);
    }

    /**
     * reflection constructor or use annotation
     */
    private function resolveObject(DefinitionEntry $entry)
    {
        if ($this->annotationReader === null) {
            $this->resolveObjectConstructor($entry);
        } else {
            $this->resolveObjectByAnnotation($entry);
        }
        return $entry;
    }

    private function resolveObjectConstructor(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        $params = $definition->getConstructorParameters();
        if (empty($params) || $params instanceof NamedParameters) {
            $className = $definition->getClassName() ?: $entry->getName();
            $definition->setConstructorParameters($this->getConstructorParameters($className, $params));
        }
    }

    private function getConstructorParameters($className, $params)
    {
        $class = $this->getReflectionClass($className);
        $namedParams = $params instanceof NamedParameters ? $params->getParameters() : $params;
        $paramTypes = [];
        if (($constructor = $class->getConstructor()) !== null) {
            foreach ($constructor->getParameters() as $i => $parameter) {
                if (isset($namedParams[$parameter->getName()])) {
                    $paramTypes[$i] = $namedParams[$parameter->getName()];
                } elseif (isset($namedParams[$i])) {
                    $paramTypes[$i] = $namedParams[$i];
                } else {
                    if ($parameter->isOptional()) {
                        continue;
                    }
                    if (($paramClass = $parameter->getClass()) !== null) {
                        $paramTypes[$i] = new AliasDefinition($paramClass->getName());
                    } else {
                        throw new DefinitionException(sprintf(
                            "The %dth parameter of constructor for class %s cannot resolved",
                            $i,
                            $className
                        ));
                    }
                }
            }
        }
        return $paramTypes;
    }

    private function resolveObjectByAnnotation(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        $className = $definition->getClassName() ?: $entry->getName();
        $class = $this->getReflectionClass($className);
        try {
            $this->readDefinition($class, $definition);
        } catch (AnnotationParseException $e) {
            throw new AnnotationException($e->getMessage());
        }
    }

    private function readDefinition(ReflectionClass $class, ObjectDefinition $definition)
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
                if ($this->logger) {
                    $this->logger->warning($e->getMessage(), ['exception' => $e]);
                }
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

    private function readProperties(ReflectionClass $class, array $exists)
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
                $entryName = $this->getDocReader()->getPropertyClass($property);
                if ($entryName === null) {
                    throw new AnnotationException(sprintf(
                        "@Inject found on property %s->%s but unable to guess what to inject, use a @var annotation",
                        $class->getName(),
                        $property->getName()
                    ));
                }
            }
            $definitions[$property->getName()] = new AliasDefinition($entryName);
        }
        return $definitions;
    }

    private function readMethods(ReflectionClass $class, array $exists)
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
                        "@Inject found on method %s::%s but unable to guess parameter"
                        . " '%s' type, use a @param annotation",
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

    private function autowire($class, &$properties, &$methods)
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
                if ($this->logger) {
                    $this->logger->warning(sprintf(
                        "Cannot resolve method '%s::%s' parameters",
                        $class->getName(),
                        $method->getName()
                    ));
                }
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
            $setter = 'set' . strtolower($property->getName());
            if (isset($methods[$setter])) {
                continue;
            }
            $type = $this->getDocReader()->getPropertyClass($property);
            if ($type !== null) {
                $properties[$property->getName()] = new AliasDefinition($type);
            }
        }
    }

    private function getReflectionClass($className)
    {
        try {
            $class = new ReflectionClass($className);
            if (!$class->isInstantiable()) {
                throw new DefinitionException(sprintf(
                    "Cannot create class instance for %s because it is an %s",
                    $className,
                    $class->isInterface() ? "interface" : "abstract class"
                ));
            }
            return $class;
        } catch (ReflectionException $e) {
            throw new DefinitionException("Cannot load class '{$className}'");
        }
    }

    private function getParameterClasses(ReflectionMethod $method)
    {
        $docParams = $this->getDocReader()->getParameterClasses($method);
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

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getDocReader()
    {
        if ($this->docReader === null) {
            $this->docReader = new DocReader();
        }
        return $this->docReader;
    }
}
