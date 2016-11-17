<?php
namespace kuiper\serializer\fixtures;

class TypeOverrideByProperty
{
    /**
     * @var Member[]
     */
    private $values;

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values)
    {
        $this->values = $values;
        return $this;
    }
}
