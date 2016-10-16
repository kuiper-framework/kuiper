<?php
namespace kuiper\di\resolver;

use Interop\Container\ContainerInterface;
use kuiper\di\DefinitionEntry;
use kuiper\di\ProxyFactory;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\ObjectDefinition;
use InvalidArgumentException;
use kuiper\di\DeferredObject;
use kuiper\di\Scope;
use ReflectionClass;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

class ObjectResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var ProxyFactory
     */
    private $proxyFactory;

    public function __construct(ResolverInterface $resolver, ProxyFactory $proxyFactory)
    {
        $this->resolver = $resolver;
        $this->proxyFactory = $proxyFactory;
    }
    
    /**
     * @inheritDoc
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof ObjectDefinition) {
            throw new InvalidArgumentException(sprintf(
                "definition expects a %s, got %s",
                ObjectDefinition::class,
                is_object($definition) ? get_class($definition) : gettype($definition)
            ));
        }
        $className = $definition->getClassName() ?: $entry->getName();
        if ($definition->isLazy() || $definition->getScope() === Scope::REQUEST) {
            return $this->proxyFactory->createProxy(
                $className,
                function () use ($container, $entry, $parameters) {
                    return $this->createInstance($container, $entry, $parameters);
                }
            );
        } else {
            return $this->createInstance($container, $entry, $parameters, true);
        }
    }

    private function createInstance($container, $entry, $parameters, $deferInit = false)
    {
        $definition = $entry->getDefinition();
        if (empty($parameters)) {
            $parameters = $this->resolveParams(
                $container,
                $entry->getName().'.constructor',
                $definition->getConstructorParameters()
            );
        }
        $className = $definition->getClassName() ?: $entry->getName();
        $instance = $this->newInstance($className, $parameters);
        $methods = $definition->getMethods();
        if ($instance instanceof LoggerAwareInterface
            && !isset($methods['setLogger'])
            && $container->has(LoggerInterface::class)) {
            $definition->method('setLogger', $container->get(LoggerInterface::class));
        }
        if (!$deferInit) {
            return $this->initializer($container, $instance, $definition);
        }
        $properties = $definition->getProperties();
        if (empty($properties) && empty($methods)) {
            return $instance;
        } else {
            return new DeferredObject($instance, function ($instance) use ($container, $definition) {
                return $this->initializer($container, $instance, $definition);
            });
        }
    }

    private function initializer($container, $instance, $definition)
    {
        $class = new ReflectionClass($instance);
        $properties = $definition->getProperties();
        if (!empty($properties)) {
            $values = $this->resolveParams($container, $class->getName().'.properties', $properties);
            foreach ($values as $name => $value) {
                $property = $class->getProperty($name);
                if ($property->isPublic()) {
                    $instance->$name = $value;
                } else {
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }
            }
        }
        $methods = $definition->getMethods();
        if (!empty($methods)) {
            $values = $this->resolveParams($container, $class->getName().'.methods', $methods);
            foreach ($values as $method => $calls) {
                foreach ($calls as $args) {
                    call_user_func_array([$instance, $method], $args);
                }
            }
        }
        return $instance;
    }

    private function resolveParams(ContainerInterface $container, $name, array $args)
    {
        return $this->resolver->resolve($container, new DefinitionEntry($name, new ArrayDefinition($args)));
    }

    protected function newInstance($className, $parameters)
    {
        $argc = count($parameters);
        if ($argc === 0) {
            return new $className;
        } elseif ($argc === 1) {
            return new $className($parameters[0]);
        } elseif ($argc === 2) {
            return new $className($parameters[0], $parameters[1]);
        } elseif ($argc === 3) {
            return new $className($parameters[0], $parameters[1], $parameters[2]);
        } else {
            $class = new ReflectionClass($className);
            return $class->newInstanceArgs($parameters);
        }
    }
}
