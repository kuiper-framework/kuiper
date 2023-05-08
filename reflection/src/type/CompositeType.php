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

namespace kuiper\reflection\type;

use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;

class CompositeType extends ReflectionType
{
    /**
     * @param ReflectionTypeInterface[] $types
     */
    public function __construct(private readonly array $types)
    {
        $allowsNull = false;
        foreach ($this->types as $type) {
            if ($type instanceof NullType) {
                $allowsNull = true;
                break;
            }
        }
        parent::__construct($allowsNull);
    }

    public static function create(array $types): ReflectionTypeInterface
    {
        if (1 === count($types)) {
            return $types[0];
        }
        if (2 === count($types)) {
            if ($types[0] instanceof NullType) {
                return $types[1]->withAllowsNull(true);
            }
            if ($types[1] instanceof NullType) {
                return $types[0]->withAllowsNull(true);
            }
        }

        return new self($types);
    }

    /**
     * @return ReflectionTypeInterface[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getName(): string
    {
        return implode('|', array_map(static function (ReflectionTypeInterface $type): string {
            return $type->getName();
        }, $this->types));
    }

    public function allowsNull(): bool
    {
        foreach ($this->types as $type) {
            if ($type->allowsNull()) {
                return true;
            }
        }

        return false;
    }

    public function __toString(): string
    {
        return implode('|', $this->types);
    }

    public function isComposite(): bool
    {
        return true;
    }
}
