<?php
namespace kuiper\di\resolver;

use Interop\Container\ContainerInterface;
use kuiper\di\DefinitionEntry;
use kuiper\di\source\EnvSource;
use kuiper\di\definition\EnvDefinition;
use InvalidArgumentException;

class EnvResolver implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof EnvDefinition) {
            throw new InvalidArgumentException(sprintf(
                "definition expects a %s, got %s",
                EnvDefinition::class,
                is_object($definition) ? get_class($definition) : gettype($definition)
            ));
        }
        $value = EnvSource::findEnvironmentVariable($definition->getName());
        return $value === false ? $definition->getDefaultValue() : $value;
    }
}
