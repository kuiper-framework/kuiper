<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\Definition;
use DI\Definition\Source\DefinitionSource;
use DI\Definition\ValueDefinition;
use kuiper\helper\PropertyResolverInterface;

class PropertiesDefinitionSource implements DefinitionSource
{
    /**
     * PropertiesDefinitionSource constructor.
     */
    public function __construct(private PropertyResolverInterface $properties, private string $prefix = 'application.')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name): ?Definition
    {
        $value = $this->properties->get($name);
        if (null !== $value || str_starts_with($name, $this->prefix)) {
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
