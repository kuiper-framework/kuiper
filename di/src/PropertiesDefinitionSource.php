<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Source\DefinitionSource;
use DI\Definition\ValueDefinition;
use kuiper\helper\Properties;

class PropertiesDefinitionSource implements DefinitionSource
{
    /**
     * @var Properties
     */
    private $properties;

    /**
     * PropertiesDefinitionSource constructor.
     */
    public function __construct($properties)
    {
        if (is_array($properties)) {
            $properties = Properties::fromArray($properties);
        }
        if (!$properties instanceof Properties) {
            throw new \InvalidArgumentException('expected array, got '.gettype($properties));
        }
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
