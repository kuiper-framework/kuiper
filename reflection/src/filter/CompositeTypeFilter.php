<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\TypeFilterInterface;
use kuiper\reflection\TypeUtils;

class CompositeTypeFilter implements TypeFilterInterface
{
    /**
     * @var CompositeType
     */
    private $type;

    /**
     * CompositeTypeFilter constructor.
     *
     * @param CompositeType $type
     */
    public function __construct(CompositeType $type)
    {
        $this->type = $type;
    }

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        foreach ($this->type->getTypes() as $type) {
            if (TypeUtils::validate($type, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        return $value;
    }
}
