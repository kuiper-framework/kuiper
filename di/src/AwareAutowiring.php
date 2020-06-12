<?php

declare(strict_types=1);

namespace kuiper\di;

use DI\Definition\ObjectDefinition;
use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionSource;

class AwareAutowiring implements DefinitionSource, Autowiring
{
    /**
     * @var Autowiring
     */
    private $autowiring;

    /**
     * @var AwareInjection[]
     */
    private $awareInjections;

    public function __construct(array $awareInjections = [], Autowiring $autowiring = null)
    {
        $this->awareInjections = $awareInjections;
        $this->autowiring = $autowiring;
    }

    public function add(AwareInjection $awareInjection, $ignoreExist = false): void
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
    public function setAutowiring($autowiring): void
    {
        $this->autowiring = $autowiring;
    }

    /**
     * {@inheritdoc}
     */
    public function autowire(string $name, ObjectDefinition $definition = null)
    {
        $definition = $this->autowiring->autowire($name, $definition);
        if ($definition && $definition instanceof ObjectDefinition) {
            $className = $definition->getClassName();
            foreach ($this->awareInjections as $awareDefinition) {
                if ($awareDefinition->match($className)) {
                    $awareDefinition->inject($definition);
                }
            }
        }

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition(string $name)
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
