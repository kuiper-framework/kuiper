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

namespace kuiper\tars\type;

use kuiper\helper\Enum;

class EnumType extends AbstractType
{
    /**
     * @var string
     */
    private $className;

    /**
     * StructType constructor.
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function asEnumType(): EnumType
    {
        return $this;
    }

    public function isEnum(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->className;
    }

    /**
     * @param mixed $enumObj
     */
    public function getEnumValue($enumObj): ?int
    {
        return is_object($enumObj) ? $enumObj->value : $enumObj;
    }

    /**
     * @param mixed $value
     *
     * @return Enum
     */
    public function createEnum($value)
    {
        return call_user_func([$this->className, 'fromValue'], $value);
    }

    public function getTarsType(): int
    {
        return Type::INT64;
    }
}
