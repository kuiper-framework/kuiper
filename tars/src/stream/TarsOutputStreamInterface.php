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

namespace kuiper\tars\stream;

use kuiper\tars\exception\TarsStreamException;
use kuiper\tars\type\MapType;
use kuiper\tars\type\StructMap;
use kuiper\tars\type\StructType;
use kuiper\tars\type\Type;
use kuiper\tars\type\VectorType;

interface TarsOutputStreamInterface
{
    public function writeBool(int $tag, bool $value): void;

    public function writeChar(int $tag, string $char): void;

    public function writeInt8(int $tag, int $value): void;

    public function writeInt16(int $tag, int $value): void;

    public function writeInt32(int $tag, int $value): void;

    public function writeInt64(int $tag, int $value): void;

    public function writeUInt8(int $tag, int $value): void;

    public function writeUInt16(int $tag, int $value): void;

    public function writeUInt32(int $tag, int $value): void;

    public function writeFloat(int $tag, float $value): void;

    public function writeDouble(int $tag, float $value): void;

    public function writeString(int $tag, string $value): void;

    /**
     * @throws TarsStreamException
     */
    public function writeStruct(int $tag, object $value, StructType $structType): void;

    /**
     * @param array|string $value
     *
     * @throws TarsStreamException
     */
    public function writeVector(int $tag, $value, VectorType $vectorType): void;

    /**
     * @param array|StructMap $value
     *
     * @throws TarsStreamException
     */
    public function writeMap(int $tag, $value, MapType $mapType): void;

    /**
     * @param mixed $value
     *
     * @throws TarsStreamException
     */
    public function write(int $tag, $value, Type $type): void;
}
