<?php
namespace kuiper\di\source;

use kuiper\helper\DotArray;
use kuiper\di\definition\ValueDefinition;
use kuiper\di\DefinitionEntry;

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
    
    public function __construct($data, $prefix = "settings")
    {
        if (!$data instanceof DotArray) {
            $data = new DotArray($data);
        }
        $this->dotArray = $data;
        $this->prefix = $prefix . '.';
        $this->prefixLength = strlen($this->prefix);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
            return new DefinitionEntry($this->prefix . $name, new ValueDefinition($value));
        }
    }
}
