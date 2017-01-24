<?php

namespace kuiper\di\source;

use kuiper\di\definition\decorator\DecoratorInterface;
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function set($name, DefinitionInterface $value)
    {
        $this->mutable->set($name, $value);
    }
}
