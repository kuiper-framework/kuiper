<?php

namespace kuiper\di\source;

use kuiper\di\definition\decorator\DecoratorInterface;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\event\DefinitionEvent;
use kuiper\di\event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SourceChain implements MutableSourceInterface
{
    /**
     * @var SourceInterface[]
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \kuiper\di\DefinitionEntry[]
     */
    private $resolvedEntries = [];

    public function __construct(array $sources, MutableSourceInterface $mutable, DecoratorInterface $decorator, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->sources = $sources;
        $this->mutable = $mutable;
        $this->decorator = $decorator;
        $this->eventDispatcher = $eventDispatcher;
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
        if ($this->eventDispatcher) {
            $event = new DefinitionEvent($name);
            $this->eventDispatcher->dispatch(Events::BEFORE_GET_DEFINITION, $event);
            if ($definition = $event->getDefinition()) {
                return $definition;
            }
        }
        foreach ($this->sources as $source) {
            $entry = $source->get($name);
            if (null !== $entry) {
                $this->resolvedEntries[$name] = $entry = $this->decorator->decorate($entry);
                if ($this->eventDispatcher) {
                    $event->setDefinition($entry);
                    $this->eventDispatcher->dispatch(Events::AFTER_GET_DEFINITION, $event);
                }

                return $entry;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, DefinitionInterface $value)
    {
        unset($this->resolvedEntries[$name]);
        $this->mutable->set($name, $value);
    }
}
