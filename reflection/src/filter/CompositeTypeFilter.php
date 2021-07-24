<?php

declare(strict_types=1);

namespace kuiper\reflection\filter;

use kuiper\reflection\type\CompositeType;
use kuiper\reflection\TypeFilterInterface;

class CompositeTypeFilter implements TypeFilterInterface
{
    /**
     * @var CompositeType
     */
    private $type;

    /**
     * CompositeTypeFilter constructor.
     */
    public function __construct(CompositeType $type)
    {
        $this->type = $type;
    }

    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     */
    public function isValid($value): bool
    {
        foreach ($this->type->getTypes() as $type) {
            if ($type->isValid($value)) {
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
