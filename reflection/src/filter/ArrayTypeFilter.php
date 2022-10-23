<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\reflection\filter;

use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\TypeFilterInterface;

class ArrayTypeFilter implements TypeFilterInterface
{
    public function __construct(private readonly ArrayType $type)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function isValid(mixed $value): bool
    {
        return $this->validateArray($value, $this->type->getValueType(), $this->type->getDimension());
    }

    /**
     * {@inheritDoc}
     */
    public function sanitize(mixed $value): array
    {
        return $this->sanitizeArray($value, $this->type->getValueType(), $this->type->getDimension());
    }

    private function validateArray(mixed $value, ReflectionTypeInterface $valueType, int $dimension): bool
    {
        if (!is_array($value)) {
            return false;
        }
        if (1 === $dimension) {
            foreach ($value as $item) {
                if (!$valueType->isValid($item)) {
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

    private function sanitizeArray(mixed $value, ReflectionTypeInterface $valueType, int $dimension): array
    {
        $result = [];
        $value = (array) $value;
        if (1 === $dimension) {
            foreach ($value as $key => $item) {
                $result[$key] = $valueType->sanitize($item);
            }
        } else {
            foreach ($value as $key => $item) {
                $result[$key] = $this->sanitizeArray($item, $valueType, $dimension - 1);
            }
        }

        return $result;
    }
}
