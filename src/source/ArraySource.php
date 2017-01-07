<?php

namespace kuiper\di\source;

use Closure;
use kuiper\di\definition\ArrayDefinition;
use kuiper\di\definition\DefinitionInterface;
use kuiper\di\definition\FactoryDefinition;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\DefinitionEntry;

class ArraySource implements MutableSourceInterface
{
    /**
     * @var array
     */
    private $definitions = [];

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return array_key_exists($name, $this->definitions);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            return null;
        }

        return $this->createEntry($name, $this->definitions[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, DefinitionInterface $value)
    {
        $this->definitions[$name] = $value;

        return $this;
    }

    /**
     * @param array $definitions
     * @param bool  $deepMerge
     */
    public function addDefinitions(array $definitions, $deepMerge = false)
    {
        if ($deepMerge) {
            $this->definitions = $this->mergeRecursive($this->definitions, $definitions);
        } else {
            $this->definitions = array_merge($this->definitions, $definitions);
        }
    }

    private function mergeRecursive($arr1, $arr2)
    {
        foreach ($arr2 as $name => $val) {
            if (is_array($val) && isset($arr1[$name]) && is_array($arr1[$name])) {
                $val = $this->mergeRecursive($arr1[$name], $val);
            }
            $arr1[$name] = $val;
        }

        return $arr1;
    }

    private function createEntry($name, $value)
    {
        if ($value instanceof Closure) {
            return new DefinitionEntry($name, new FactoryDefinition($value));
        } elseif ($value instanceof DefinitionInterface) {
            return new DefinitionEntry($name, $value);
        } elseif (is_array($value)) {
            return new DefinitionEntry($name, new ArrayDefinition($value));
        } else {
            return new DefinitionEntry($name, new ValueDefinition($value));
        }
    }
}
