<?php
namespace kuiper\di\resolver;

use Interop\Container\ContainerInterface;
use kuiper\di\DefinitionEntry;
use kuiper\di\source\EnvSource;
use kuiper\di\definition\StringDefinition;
use InvalidArgumentException;
use kuiper\di\exception\DependencyException;
use Exception;

class StringResolver implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(ContainerInterface $container, DefinitionEntry $entry, $parameters = [])
    {
        $definition = $entry->getDefinition();
        if (!$definition instanceof StringDefinition) {
            throw new InvalidArgumentException(sprintf(
                "definition expects a %s, got %s",
                StringDefinition::class,
                is_object($definition) ? get_class($definition) : gettype($definition)
            ));
        }
        return preg_replace_callback('#\{([^\{\}]+)\}#', function (array $matches) use ($container) {
            try {
                return $container->get($matches[1]);
            } catch (Exception $e) {
                throw new DependencyException(sprintf(
                    "Error while parsing string expression for entry '%s': %s",
                    $this->getName(),
                    $e->getMessage()
                ), 0, $e);
            }
        }, $definition->getExpression());
    }
}
