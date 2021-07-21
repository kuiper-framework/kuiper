<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\tars\protocol\type\MapType;
use kuiper\tars\protocol\type\StructMap;
use kuiper\tars\protocol\type\StructType;
use kuiper\tars\protocol\type\Type;
use kuiper\tars\protocol\type\VectorType;

interface TarsInputStreamInterface
{
    public function readBool(int $tag, bool $require): ?bool;

    public function readChar(int $tag, bool $require): ?string;

    public function readInt8(int $tag, bool $require): ?int;

    public function readInt16(int $tag, bool $require): ?int;

    public function readInt32(int $tag, bool $require): ?int;

    public function readInt64(int $tag, bool $require): ?int;

    public function readFloat(int $tag, bool $require): ?float;

    public function readDouble(int $tag, bool $require): ?float;

    public function readUInt8(int $tag, bool $require): ?int;

    public function readUInt16(int $tag, bool $require): ?int;

    public function readUInt32(int $tag, bool $require): ?int;

    public function readString(int $tag, bool $require): ?string;

    public function readStruct(int $tag, bool $require, StructType $structType): ?object;

    /**
     * @return array|string|null
     */
    public function readVector(int $tag, bool $require, VectorType $vectorType);

    /**
     * @return array|StructMap|null
     */
    public function readMap(int $tag, bool $require, MapType $mapType);

    /**
     * @return mixed
     */
    public function read(int $tag, bool $require, Type $type);
}
