<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\TypeFilterInterface;

class FloatTypeFilter implements TypeFilterInterface
{
    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
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
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}
