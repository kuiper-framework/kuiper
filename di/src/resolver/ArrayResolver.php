<?php

namespace kuiper\di\resolver;

use Closure;
use kuiper\di\ContainerInterface;
use InvalidArgumentException;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\DefinitionEntry;

class ArrayResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    private $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof ArrayDefinition) {
            throw new InvalidArgumentException(sprintf(
                'definition expects a %s, got %s',
                ArrayDefinition::class,
                is_object($definition) ? get_class($definition) : gettype($definition)
            ));
        }

        return $this->resolveArray($container, $entry->getName(), $definition->getValues());
    }

    private function resolveArray($container, $prefix, $values)
    {
        $resolved = [];
        foreach ($values as $i => $value) {
            $name = $prefix.'['.$i.']';
            if (is_array($value)) {
                $resolved[$i] = $this->resolveArray($container, $name, $value);
            } elseif ($value instanceof ArrayDefinition) {
                $resolved[$i] = $this->resolveArray($container, $name, $value->getValues());
            } elseif ($value instanceof Closure) {
                $resolved[$i] = $this->resolver->resolve($container, new DefinitionEntry($name, new FactoryDefinition($value)));
            } elseif ($value instanceof DefinitionInterface) {
                $resolved[$i] = $this->resolver->resolve($container, new DefinitionEntry($name, $value));
            } else {
                $resolved[$i] = $value;
            }
        }

        return $resolved;
    }
}
