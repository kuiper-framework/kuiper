<?php
namespace kuiper\di\definition;

trait SerializeTrait
{
    public function serialize()
    {
        return serialize(get_object_vars($this));
    }

    public function unserialize($data)
    {
        foreach (unserialize($data) as $key => $val) {
            $this->$key = $val;
        }
    }
}
