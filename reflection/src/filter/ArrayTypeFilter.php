<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\TypeFilterInterface;
use kuiper\reflection\TypeUtils;

class ArrayTypeFilter implements TypeFilterInterface
{
    /**
     * @var ArrayType
     */
    private $type;

    /**
     * ArrayTypeFilter constructor.
     *
     * @param ArrayType $type
     */
    public function __construct(ArrayType $type)
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
        return $this->validateArray($value, $this->type->getValueType(), $this->type->getDimension());
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
        return $this->sanitizeArray($value, $this->type->getValueType(), $this->type->getDimension());
    }

    private function validateArray($value, ReflectionTypeInterface $valueType, $dimension)
    {
        if (!is_array($value)) {
            return false;
        }
        if (1 == $dimension) {
            foreach ($value as $item) {
                if (!TypeUtils::validate($valueType, $item)) {
                    return false;
                }
            }
        } else {
            foreach ($value as $item) {
                if (!$this->validateArray($item, $valueType, $dimension - 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function sanitizeArray($value, ReflectionTypeInterface $valueType, $dimension)
    {
        $result = [];
        $value = (array) $value;
        if (1 == $dimension) {
            foreach ($value as $key => $item) {
                $result[$key] = TypeUtils::sanitize($valueType, $item);
            }
        } else {
            foreach ($value as $key => $item) {
                $result[$key] = $this->sanitizeArray($item, $valueType, $dimension - 1);
            }
        }

        return $result;
    }
}
