<?php

namespace kuiper\di\definition\decorator;

use Closure;
use kuiper\di\definition\AliasDefinition;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\NamedParameters;
use kuiper\di\definition\ObjectDefinition;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\DefinitionEntry;
use kuiper\di\exception\DefinitionException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

class DefinitionDecorator implements DecoratorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function decorate(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        if ($definition instanceof FactoryDefinition) {
            return $this->resolveFactoryParams($entry);
        } elseif ($definition instanceof ObjectDefinition) {
            return $this->resolveObjectConstructorParams($entry);
        } else {
            return $entry;
        }
    }

    /**
     * reflection closure parameters.
     */
    protected function resolveFactoryParams(DefinitionEntry $entry)
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
            throw new DefinitionException('Invalid factory for entry '.$entry->getName());
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
        $newDef->scope($definition->getScope());

        return new DefinitionEntry($entry->getName(), $newDef);
    }

    /**
     * reflection constructor or use annotation.
     */
    protected function resolveObjectConstructorParams(DefinitionEntry $entry)
    {
        $definition = $entry->getDefinition();
        $params = $definition->getConstructorParameters();
        if (empty($params) || $params instanceof NamedParameters) {
            $className = $definition->getClassName() ?: $entry->getName();
            $definition->setConstructorParameters($this->getConstructorParameters($className, $params));
        }

        return $entry;
    }

    protected function getConstructorParameters($className, $params)
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
                            'The %dth parameter of constructor for class %s cannot resolved',
                            $i,
                            $className
                        ));
                    }
                }
            }
        }

        return $paramTypes;
    }

    protected function getReflectionClass($className)
    {
        try {
            $class = new ReflectionClass($className);
            if (!$class->isInstantiable()) {
                throw new DefinitionException(sprintf(
                    'Cannot create class instance for %s because it is an %s',
                    $className,
                    $class->isInterface() ? 'interface' : 'abstract class'
                ));
            }

            return $class;
        } catch (ReflectionException $e) {
            throw new DefinitionException("Cannot load class '{$className}'");
        }
    }
}
