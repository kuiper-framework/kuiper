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

use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\DefinitionSource;
use DI\DependencyException;

class ConditionDefinitionSource implements DefinitionSource, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var ConditionDefinition[][]|mixed
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
        if (null !== $definition) {
            return $definition;
        }
        if (!isset($this->definitions[$name])) {
            return null;
        }
        if (isset($this->resolving[$name])) {
            throw new DependencyException("Circular dependency detected while trying to resolve entry '$name'");
        }
        $this->resolving[$name] = true;
        $conditionDefs = $this->definitions[$name];
        foreach (array_reverse($conditionDefs) as $conditionDef) {
            if (!$conditionDef instanceof ConditionDefinition) {
                throw new \InvalidArgumentException("Definition '$name' is not ConditionalDefinition");
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
