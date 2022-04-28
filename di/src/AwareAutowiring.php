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
use DI\Definition\ObjectDefinition;
use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionSource;

class AwareAutowiring implements DefinitionSource, Autowiring
{
    /**
     * AwareAutowiring constructor.
     *
     * @param AwareInjection[] $awareInjections
     * @param Autowiring|null  $autowiring
     */
    public function __construct(private array $awareInjections = [], private ?Autowiring $autowiring = null)
    {
    }

    public function add(AwareInjection $awareInjection, bool $ignoreExist = false): void
    {
        if (!$ignoreExist && isset($this->awareInjections[$awareInjection->getInterfaceName()])) {
            throw new \InvalidArgumentException($awareInjection->getInterfaceName().' is injected');
        }
        $this->awareInjections[$awareInjection->getInterfaceName()] = $awareInjection;
    }

    public function hasInjections(): bool
    {
        return !empty($this->awareInjections);
    }

    /**
     * @param Autowiring $autowiring
     */
    public function setAutowiring(Autowiring $autowiring): void
    {
        $this->autowiring = $autowiring;
    }

    /**
     * {@inheritdoc}
     */
    public function autowire(string $name, ?ObjectDefinition $definition = null): ?ObjectDefinition
    {
        $autowired = $this->autowiring->autowire($name, $definition);
        if ($autowired instanceof ObjectDefinition) {
            $className = $autowired->getClassName();
            foreach ($this->awareInjections as $awareDefinition) {
                if ($awareDefinition->match($className)) {
                    $awareDefinition->inject($autowired);
                }
            }
        }

        return $autowired;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name): ?Definition
    {
        return $this->autowire($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitions(): array
    {
        return [];
    }
}
