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
use DI\Definition\Exception\InvalidDefinition;
use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\DefinitionSource;
use InvalidArgumentException;

class ConditionDefinitionSource implements DefinitionSource, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private DefinitionArray $source;

    private array $resolving;

    public function __construct(private array $definitions, Autowiring $autowiring = null)
    {
        $this->source = new DefinitionArray([], $autowiring);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name): ?Definition
    {
        $definition = $this->source->getDefinition($name);
        if (null !== $definition) {
            return $definition;
        }
        if (!isset($this->definitions[$name])) {
            return null;
        }
        if (isset($this->resolving[$name])) {
            throw new InvalidDefinition("Circular dependency detected while trying to resolve entry '$name'");
        }
        $this->resolving[$name] = true;
        $conditionDefs = $this->definitions[$name];
        foreach (array_reverse($conditionDefs) as $conditionDef) {
            if (!$conditionDef instanceof ConditionDefinition) {
                throw new InvalidArgumentException("Definition '$name' is not ConditionalDefinition");
            }
            if (!$conditionDef->matches($this->container)) {
                continue;
            }
            unset($this->resolving[$name]);
            $this->source->addDefinition($conditionDef->getDefinition());

            return $this->source->getDefinition($name);
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
