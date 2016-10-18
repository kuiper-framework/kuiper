<?php
namespace kuiper\rpc\client\fixtures;

class Item
{
    /**
     * @var string
     */
    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}