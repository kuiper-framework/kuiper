<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\tars\protocol\type\MapType;
use kuiper\tars\protocol\type\StructMap;
use kuiper\tars\protocol\type\StructType;
use kuiper\tars\protocol\type\Type;
use kuiper\tars\protocol\type\VectorType;

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

    public function writeStruct(int $tag, object $value, StructType $structType): void;

    /**
     * @param array|string $value
     */
    public function writeVector(int $tag, $value, VectorType $vectorType): void;

    /**
     * @param array|StructMap $value
     */
    public function writeMap(int $tag, $value, MapType $mapType): void;

    /**
     * @param mixed $value
     */
    public function write(int $tag, $value, Type $type): void;
}
