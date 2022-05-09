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

use kuiper\helper\Enum;
use kuiper\tars\exception\TarsStreamException;
use kuiper\tars\type\MapType;
use kuiper\tars\type\StructMap;
use kuiper\tars\type\StructMapEntry;
use kuiper\tars\type\StructType;
use kuiper\tars\type\Type;
use kuiper\tars\type\VectorType;
use phpDocumentor\Reflection\Types\Integer;
use Webmozart\Assert\Assert;

class TarsOutputStream implements TarsOutputStreamInterface
{
    /**
     * @var resource
     */
    private $buffer;

    private int $length = 0;

    private readonly bool $hasLengthHead;

    /**
     * TarsOutputStream constructor.
     */
    public function __construct(bool $hasLengthHead = false)
    {
        $this->buffer = fopen('php://temp', 'rb+');
        $this->hasLengthHead = $hasLengthHead;
        if ($hasLengthHead) {
            fwrite($this->buffer, pack('N', 0));
            $this->length = 4;
        }
    }

    public function __toString(): string
    {
        if ($this->hasLengthHead) {
            rewind($this->buffer);
            fwrite($this->buffer, pack('N', $this->length));
        }
        rewind($this->buffer);

        return stream_get_contents($this->buffer);
    }

    private function writeHead(int $tag, int $tarsType): void
    {
        //tag大于15 pack方式不一样
        if ($tag < 15) {
            /* tag first , type second */
            //	tag小于15的 只需要一个字节
            $header = ($tag << 4) | $tarsType;
            fwrite($this->buffer, chr($header));
            ++$this->length;
        } else {
            /* tag first , type second */
            //把tag放到高4位，type对应的类型数字放到低四位
            $header = (TarsConst::MAX_TAG_VALUE << 4) | $tarsType;
            fwrite($this->buffer, chr($header).chr($tag));
            $this->length += 2;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeBool(int $tag, bool $value): void
    {
        $this->writeInt8($tag, (int) $value);
    }

    /**
     * {@inheritDoc}
     */
    public function writeInt8(int $tag, int $value): void
    {
        if (0 === $value) {
            $this->writeHead($tag, Type::ZERO);
        } else {
            $this->writeHead($tag, Type::INT8);
            fwrite($this->buffer, pack(TarsConst::PACK_UINT8, $value));
            ++$this->length;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeChar(int $tag, string $char): void
    {
        $this->writeInt8($tag, '' === $char ? 0 : ord($char[0]));
    }

    /**
     * {@inheritDoc}
     */
    public function writeInt16(int $tag, int $value): void
    {
        if ($value >= TarsConst::MIN_INT8 && $value <= TarsConst::MAX_INT8) {
            $this->writeInt8($tag, $value);
        } else {
            $this->writeHead($tag, Type::INT16);
            fwrite($this->buffer, pack(TarsConst::PACK_UINT16, $value));
            $this->length += 2;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeInt32(int $tag, int $value): void
    {
        if ($value >= TarsConst::MIN_INT16 && $value <= TarsConst::MAX_INT16) {
            $this->writeInt16($tag, $value);
        } else {
            $this->writeHead($tag, Type::INT32);
            fwrite($this->buffer, pack(TarsConst::PACK_UINT32, $value));
            $this->length += 4;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeInt64(int $tag, int $value): void
    {
        if ($value >= TarsConst::MIN_INT32 && $value <= TarsConst::MAX_INT32) {
            $this->writeInt32($tag, $value);
        } else {
            $this->writeHead($tag, Type::INT64);
            fwrite($this->buffer, pack(TarsConst::PACK_UINT64, $value));
            $this->length += 8;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeUInt8(int $tag, int $value): void
    {
        Assert::true($value >= 0 && $value <= TarsConst::MAX_UINT8, "expect $value uint8");
        $this->writeInt16($tag, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function writeUInt16(int $tag, int $value): void
    {
        Assert::true($value >= 0 && $value <= TarsConst::MAX_UINT16, "expect $value uint16");
        $this->writeInt32($tag, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function writeUInt32(int $tag, int $value): void
    {
        Assert::true($value >= 0 && $value <= TarsConst::MAX_UINT32, "expect $value uint32");
        $this->writeInt64($tag, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function writeFloat(int $tag, float $value): void
    {
        if ($value < PHP_FLOAT_EPSILON && $value > -PHP_FLOAT_EPSILON) {
            $this->writeHead($tag, Type::ZERO);
        } else {
            $this->writeHead($tag, Type::FLOAT);
            fwrite($this->buffer, pack(TarsConst::PACK_FLOAT, $value));
            $this->length += 4;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeDouble(int $tag, float $value): void
    {
        if ($value < PHP_FLOAT_EPSILON && $value > -PHP_FLOAT_EPSILON) {
            $this->writeHead($tag, Type::ZERO);
        } else {
            $this->writeHead($tag, Type::DOUBLE);
            fwrite($this->buffer, pack(TarsConst::PACK_DOUBLE, $value));
            $this->length += 8;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeString(int $tag, string $value): void
    {
        $len = strlen($value);

        if ($len <= TarsConst::MAX_STRING1_LEN) {
            $this->writeHead($tag, Type::STRING1);
            fwrite($this->buffer, pack('C', $len));
            ++$this->length;
        } else {
            $this->writeHead($tag, Type::STRING4);
            fwrite($this->buffer, pack('N', $len));
            $this->length += 4;
        }
        fwrite($this->buffer, $value);
        $this->length += $len;
    }

    /**
     * {@inheritDoc}
     */
    public function writeStruct(int $tag, object $value, StructType $structType): void
    {
        $this->writeHead($tag, Type::STRUCT_BEGIN);
        foreach ($structType->getFields() as $field) {
            /** @phpstan-ignore-next-line */
            $fieldValue = $value->{$field->getName()};
            if (null === $fieldValue && !$field->isRequired()) {
                continue;
            }
            $this->write($field->getTag(), $fieldValue, $field->getType());
        }
        $this->writeHead(0, Type::STRUCT_END);
    }

    /**
     * {@inheritDoc}
     */
    public function writeVector(int $tag, array|string $value, VectorType $vectorType): void
    {
        if ($vectorType->getSubType()->isPrimitive()
            && Type::INT8 === $vectorType->getSubType()->getTarsType()) {
            if (!is_string($value)) {
                throw TarsStreamException::typeNotMatch('expect string, got '.gettype($value));
            }
            $this->writeHead($tag, Type::SIMPLE_LIST);
            $this->writeHead(0, Type::INT8);
            $len = strlen($value);
            $this->writeInt32(0, $len);
            fwrite($this->buffer, $value);
            $this->length += $len;
        } else {
            if (!is_array($value)) {
                throw TarsStreamException::typeNotMatch('expect array, got '.gettype($value));
            }
            $cnt = count($value);
            $this->writeHead($tag, Type::VECTOR);
            $this->writeInt32(0, $cnt);
            foreach ($value as $item) {
                $this->write(0, $item, $vectorType->getSubType());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function writeMap(int $tag, StructMap|array $value, MapType $mapType): void
    {
        /** @var mixed $value */
        if (!is_array($value) && !($value instanceof StructMap)) {
            throw TarsStreamException::typeNotMatch('Expect array or StructMap, got '.gettype($value));
        }
        $cnt = count($value);
        $this->writeHead($tag, Type::MAP);
        $this->writeInt32(0, $cnt);

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->write(0, $key, $mapType->getKeyType());
                $this->write(1, $item, $mapType->getValueType());
            }
        } else {
            /** @var StructMapEntry $entry */
            foreach ($value as $entry) {
                $this->write(0, $entry->getKey(), $mapType->getKeyType());
                $this->write(1, $entry->getValue(), $mapType->getValueType());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write(int $tag, mixed $value, Type $type): void
    {
        if ($type->isPrimitive()) {
            // todo 检查类型
            switch ($type->asPrimitiveType()->getTarsType()) {
                case Type::INT8:
                    $this->writeInt8($tag, (int) ($value ?? 0));
                    break;
                case Type::INT16:
                    $this->writeInt16($tag, (int) ($value ?? 0));
                    break;
                case Type::INT32:
                    $this->writeInt32($tag, (int) ($value ?? 0));
                    break;
                case Type::INT64:
                    $this->writeInt64($tag, (int) ($value ?? 0));
                    break;
                case Type::FLOAT:
                    $this->writeFloat($tag, (float) ($value ?? 0));
                    break;
                case Type::DOUBLE:
                    $this->writeDouble($tag, (float) ($value ?? 0));
                    break;
                case Type::STRING4:
                    $this->writeString($tag, (string) ($value ?? ''));
                    break;
                default:
                    throw TarsStreamException::typeNotMatch('unknown primitive type '.$type);
            }
        } elseif ($type->isEnum()) {
            if (null === $value) {
                $value = 0;
            }
            if (is_int($value)) {
                $this->writeInt64($tag, $value);
            } elseif (is_object($value) && ($value instanceof Enum)) {
                $this->writeInt64($tag, $value->value());
            } else {
                throw TarsStreamException::typeNotMatch('Expect enum value, got '.gettype($value));
            }
        } elseif ($type->isVector()) {
            $this->writeVector($tag, $value ?? [], $type->asVectorType());
        } elseif ($type->isMap()) {
            $this->writeMap($tag, $value ?? [], $type->asMapType());
        } elseif ($type->isStruct()) {
            if (null === $value) {
                $className = $type->asStructType()->getClassName();
                $value = new $className();
            }
            if (!is_object($value)) {
                throw TarsStreamException::typeNotMatch('expect object, got '.gettype($value));
            }
            $this->writeStruct($tag, $value, $type->asStructType());
        } elseif (!$type->isVoid()) {
            throw TarsStreamException::typeNotMatch('Expect type one of primitive,enum,struct,vector,map, got '.get_class($type));
        }
    }

    /**
     * @param mixed $data
     *
     * @throws TarsStreamException
     */
    public static function pack(Type $type, $data): string
    {
        $os = new TarsOutputStream();
        $os->write(0, $data, $type);

        return (string) $os;
    }
}
