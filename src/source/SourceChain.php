<?php
namespace kuiper\di\source;

use kuiper\di\definition\DecoratorInterface;
use kuiper\di\definition\DefinitionInterface;

class SourceChain implements MutableSourceInterface
{
    /**
     * @var array<SourceInterface>
     */
    private $sources;

    /**
     * @var MutableSourceInterface
     */
    private $mutable;

    /**
     * @var DecoratorInterface
     */
    private $decorator;

    /**
     * @var array<\kuiper\di\DefinitionEntry>
     */
    private $resolvedEntries = [];
    
    public function __construct(array $sources, MutableSourceInterface $mutable, DecoratorInterface $decorator)
    {
        $this->sources = $sources;
        $this->mutable = $mutable;
        $this->decorator = $decorator;
    }

    /**
     * @inheritDoc
     */
    public function has($name)
    {
        if (isset($this->resolvedEntries[$name])) {
            return true;
        }
        foreach ($this->sources as $source) {
            if ($source->has($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function get($name)
    {
        if (isset($this->resolvedEntries[$name])) {
            return $this->resolvedEntries[$name];
        }
        foreach ($this->sources as $source) {
            $entry = $source->get($name);
            if ($entry !== null) {
                return $this->resolvedEntries[$name] = $this->decorator->decorate($entry);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function set($name, DefinitionInterface $value)
    {
        $this->mutable->set($name, $value);
    }
}
