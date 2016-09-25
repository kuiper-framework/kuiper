<?php
namespace kuiper\di\resolver;

use Interop\Container\ContainerInterface;
use kuiper\di\DefinitionEntry;
use kuiper\di\source\ArraySource;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\DefinitionInterface;
use InvalidArgumentException;
use Closure;

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
     * @inheritDoc
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof ArrayDefinition) {
            throw new InvalidArgumentException(sprintf(
                "definition expects a %s, got %s",
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
            } else {
                $entry = $this->createEntry($name, $value);
                $resolved[$i] = $this->resolver->resolve($container, $entry);
            }
        }
        return $resolved;
    }

    private function createEntry($name, $value)
    {
        if ($value instanceof Closure) {
            return new DefinitionEntry($name, new FactoryDefinition($value));
        } elseif ($value instanceof DefinitionInterface) {
            return new DefinitionEntry($name, $value);
        } else {
            return new DefinitionEntry($name, new ValueDefinition($value));
        }
    }
}
