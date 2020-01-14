<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\DefinitionSource;
use DI\DependencyException;

class ConditionalDefinitionSource implements DefinitionSource, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private $definitions;

    /**
     * @var DefinitionArray
     */
    private $source;
    /**
     * @var array
     */
    private $resolving;

    public function __construct(array $definitions, Autowiring $autowiring = null)
    {
        $this->source = new DefinitionArray([], $autowiring);
        $this->definitions = $definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name)
    {
        $definition = $this->source->getDefinition($name);
        if ($definition) {
            return $definition;
        }
        if (!isset($this->definitions[$name])) {
            return null;
        }
        if (isset($this->resolving[$name])) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$name'");
        }
        $this->resolving[$name] = true;
        $definition = $this->definitions[$name];
        if (!$definition instanceof ConditionalDefinition) {
            throw new \InvalidArgumentException("Definition '$name' is not ConditionalDefinition");
        }
        if (!$definition->match($this->container)) {
            unset($this->definitions[$name], $this->resolving[$name]);

            return null;
        }
        $this->source->addDefinition($definition->getDefinition());
        unset($this->resolving[$name]);

        return $definition->getDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitions(): array
    {
        return [];
    }
}
