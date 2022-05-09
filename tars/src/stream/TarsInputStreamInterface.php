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

interface TarsInputStreamInterface
{
    /**
     * @throws TarsStreamException
     */
    public function readBool(int $tag, bool $require): ?bool;

    /**
     * @throws TarsStreamException
     */
    public function readChar(int $tag, bool $require): ?string;

    /**
     * @throws TarsStreamException
     */
    public function readInt8(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readInt16(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readInt32(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readInt64(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readFloat(int $tag, bool $require): ?float;

    /**
     * @throws TarsStreamException
     */
    public function readDouble(int $tag, bool $require): ?float;

    /**
     * @throws TarsStreamException
     */
    public function readUInt8(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readUInt16(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readUInt32(int $tag, bool $require): ?int;

    /**
     * @throws TarsStreamException
     */
    public function readString(int $tag, bool $require): ?string;

    /**
     * @throws TarsStreamException
     */
    public function readStruct(int $tag, bool $require, StructType $structType): ?object;

    /**
     * @throws TarsStreamException
     */
    public function readVector(int $tag, bool $require, VectorType $vectorType): array|string|null;

    /**
     * @throws TarsStreamException
     */
    public function readMap(int $tag, bool $require, MapType $mapType): StructMap|array|null;

    /**
     * @throws TarsStreamException
     */
    public function read(int $tag, bool $require, Type $type): mixed;
}
