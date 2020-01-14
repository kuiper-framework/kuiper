<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Source\DefinitionSource;
use DI\Definition\ValueDefinition;

class PropertiesDefinitionSource implements DefinitionSource
{
    /**
     * @var PropertyResolverInterface
     */
    private $properties;

    /**
     * PropertiesDefinitionSource constructor.
     */
    public function __construct(PropertyResolverInterface $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name)
    {
        $value = $this->properties->get($name);
        if (null !== $value) {
            return new ValueDefinition($value);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitions(): array
    {
        return [];
    }
}
