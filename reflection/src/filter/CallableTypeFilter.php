<?php

declare(strict_types=1);

namespace kuiper\reflection\filter;

use kuiper\reflection\TypeFilterInterface;

class CallableTypeFilter implements TypeFilterInterface
{
    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        return is_callable($value);
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
