<?php

namespace kuiper\di\source;

use kuiper\di\definition\ValueDefinition;
use kuiper\di\DefinitionEntry;
use kuiper\helper\DotArray;

class DotArraySource implements SourceInterface
{
    /**
     * @var DotArray
     */
    private $dotArray;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $prefixLength;

    public function __construct($data, $prefix = 'settings')
    {
        if (!$data instanceof DotArray) {
            $data = new DotArray($data);
        }
        $this->dotArray = $data;
        $this->prefix = $prefix.'.';
        $this->prefixLength = strlen($this->prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        if ($this->dotArray->isLeaf($name)) {
            // ignore name don't contain '.'
            return false;
        }
        if (strpos($name, $this->prefix) === 0) {
            $name = substr($name, $this->prefixLength);
        }

        return $this->dotArray->offsetExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if ($this->dotArray->isLeaf($name)) {
            return null;
        }
        if (strpos($name, $this->prefix) === 0) {
            $name = substr($name, $this->prefixLength);
        }
        $value = $this->dotArray->offsetGet($name);
        if (isset($value)) {
            return new DefinitionEntry($this->prefix.$name, new ValueDefinition($value));
        }
    }
}
