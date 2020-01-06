<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\TypeFilterInterface;

class IntegerTypeFilter implements TypeFilterInterface
{
    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        return false !== filter_var($value, FILTER_VALIDATE_INT);
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
        return intval($value);
    }
}
