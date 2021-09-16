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

interface Type
{
    public const ZERO = 12;
    public const STRING4 = 7;
    public const STRING1 = 6;
    public const FLOAT = 4;
    public const STRUCT_BEGIN = 10;
    public const INT64 = 3;
    public const STRUCT_END = 11;
    public const INT32 = 2;
    public const SIMPLE_LIST = 13;
    public const INT8 = 0;
    public const VECTOR = 9;
    public const DOUBLE = 5;
    public const MAP = 8;
    public const INT16 = 1;

    public function isPrimitive(): bool;

    public function isStruct(): bool;

    public function isVector(): bool;

    public function isMap(): bool;

    public function isEnum(): bool;

    public function isVoid(): bool;

    public function asPrimitiveType(): PrimitiveType;

    public function asVectorType(): VectorType;

    public function asMapType(): MapType;

    public function asEnumType(): EnumType;

    public function asStructType(): StructType;

    public function getTarsType(): int;

    public function __toString(): string;
}
