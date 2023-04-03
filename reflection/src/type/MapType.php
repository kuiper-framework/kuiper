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

class MapType extends ReflectionType
{
    public function __construct(
        private readonly ReflectionTypeInterface $keyType,
        private readonly ReflectionTypeInterface $valueType,
        bool $allowsNull = false)
    {
        parent::__construct($allowsNull);
    }

    /**
     * @return ReflectionTypeInterface
     */
    public function getKeyType(): ReflectionTypeInterface
    {
        return $this->keyType;
    }

    public function getValueType(): ReflectionTypeInterface
    {
        return $this->valueType;
    }

    public function getName(): string
    {
        return sprintf('array<%s, %s>', $this->keyType, $this->valueType);
    }

    public function isArray(): bool
    {
        return true;
    }

    public function isCompound(): bool
    {
        return $this->isUnknown();
    }

    public function isUnknown(): bool
    {
        return $this->valueType instanceof MixedType;
    }

    public function isPrimitive(): bool
    {
        return $this->isCompound();
    }
}
