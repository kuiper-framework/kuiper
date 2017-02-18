<?php

namespace kuiper\di\resolver;

use kuiper\di\ContainerInterface;
use InvalidArgumentException;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\DefinitionEntry;
use kuiper\di\ProxyFactory;
use kuiper\di\Scope;
use LogicException;

class FactoryResolver implements ResolverInterface
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
     * {@inheritdoc}
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof FactoryDefinition) {
            throw new InvalidArgumentException(sprintf(
                'definition expects a %s, got %s',
                FactoryDefinition::class,
                is_object($definition) ? get_class($definition) : gettype($definition)
            ));
        }
        if ($definition->isLazy() || $definition->getScope() === Scope::REQUEST) {
            $className = $definition->getReturnType() ?: $entry->getName();
            if (!interface_exists($className) && !class_exists($className)) {
                throw new LogicException(sprintf("Factory definition for entry '%s' requires return type", $entry->getName()));
            }

            return $this->proxyFactory->createProxy($className, function () use ($container, $entry, $parameters) {
                return $this->createInstance($container, $entry, $parameters);
            });
        } else {
            return $this->createInstance($container, $entry, $parameters);
        }
    }

    private function createInstance($container, $entry, $parameters)
    {
        $definition = $entry->getDefinition();
        $factory = $definition->getFactory();
        if (is_array($factory) && $factory[0] instanceof DefinitionInterface) {
            $factory[0] = $this->resolver->resolve(
                $container,
                new DefinitionEntry($entry->getName().'.factory', $factory[0])
            );
        }
        if (empty($parameters)) {
            $args = $definition->getArguments();
            if (!empty($args)) {
                $parameters = $this->resolver->resolve(
                    $container,
                    new DefinitionEntry($entry->getName().'.arguments', new ArrayDefinition($args))
                );
            }
        }

        return call_user_func_array($factory, $parameters);
    }
}
