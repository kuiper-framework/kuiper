<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\tars\protocol\type\MapType;
use kuiper\tars\protocol\type\PrimitiveType;
use kuiper\tars\protocol\type\StructMap;
use kuiper\tars\protocol\type\StructType;
use kuiper\tars\protocol\type\Type;
use kuiper\tars\protocol\type\VectorType;

class TarsInputStream implements TarsInputStreamInterface
{
    private const TYPE_ALIAS = [
        TarsType::INT8 => [TarsType::ZERO],
        TarsType::INT16 => [TarsType::INT8, TarsType::ZERO],
        TarsType::INT32 => [TarsType::INT16, TarsType::INT8, TarsType::ZERO],
        TarsType::INT64 => [TarsType::INT32, TarsType::INT16, TarsType::INT8, TarsType::ZERO],
        TarsType::FLOAT => [TarsType::ZERO],
        TarsType::DOUBLE => [TarsType::ZERO],
        TarsType::STRING4 => [TarsType::STRING1],
        TarsType::VECTOR => [TarsType::SIMPLE_LIST],
    ];

    /**
     * @var resource
     */
    private $buffer;

    /**
     * TarsInputStream constructor.
     *
     * @param string|resource|mixed $buffer
     */
    public function __construct($buffer)
    {
        if (is_resource($buffer)) {
            $this->buffer = $buffer;
        } elseif (is_string($buffer)) {
            $resource = fopen('php://temp', 'rb+');
            fwrite($resource, $buffer);
            rewind($resource);
            $this->buffer = $resource;
        } else {
            throw new \InvalidArgumentException('buffer should be string or resource, got '.gettype($buffer));
        }
    }

    private function readInternal(int $length): string
    {
        $char = fread($this->buffer, $length);
        if (false === $char) {
            throw TarsException::streamLenError();
        }

        return $char;
    }

    private function readHead(?int &$tag, ?int &$type, bool $require): bool
    {
        $header = fread($this->buffer, 1);
        if (false === $header || '' === $header) {
            if ($require) {
                throw TarsException::streamLenError();
            } else {
                return false;
            }
        }
        $tagAndType = ord($header);
        $type = $tagAndType & 0xF;
        $tag = $tagAndType >> 4;

        if (TarsConst::MAX_TAG_VALUE === $tag) {
            $tag = ord($this->readInternal(1));
        }

        return true;
    }

    private function matchTag(?int $tag): int
    {
        if (!$this->readHead($nextTag, $nextType, true)) {
            throw TarsException::tagNotMatch();
        }
        if (null !== $tag && $nextTag !== $tag) {
            throw TarsException::tagNotMatch();
        }

        return $nextType;
    }

    private function pushHeadBack(int $tag): void
    {
        if ($tag >= TarsConst::MAX_TAG_VALUE) {
            fseek($this->buffer, -2, SEEK_CUR);
        } else {
            fseek($this->buffer, -1, SEEK_CUR);
        }
    }

    private function match(int $tag, ?int $type, bool $require): ?int
    {
        if (!$this->readHead($nextTag, $nextType, $require)) {
            return null;
        }
        if (TarsType::STRUCT_END === $nextType) {
            if (TarsType::STRUCT_END === $type || !$require) {
                $this->pushHeadBack($nextTag);

                return null;
            }
            throw TarsException::tagNotMatch();
        }
        if (TarsType::STRUCT_END !== $type) {
            if ($nextTag === $tag) {
                if ($nextType !== $type && !in_array($nextType, self::TYPE_ALIAS[$type] ?? [], true)) {
                    throw TarsException::typeNotMatch("Expected type $type, got $nextType");
                }

                return $nextType;
            }
            if ($nextTag > $tag) {
                if ($require) {
                    throw TarsException::tagNotMatch();
                } else {
                    $this->pushHeadBack($nextTag);

                    return null;
                }
            }
        }
        $this->skipField($nextType);

        return $this->match($tag, $type, $require);
    }

    private function skipField(int $type): void
    {
        switch ($type) {
            case TarsType::ZERO:
                break;
            case TarsType::INT8:
                $this->readInternal(1);
                break;
            case TarsType::INT16:
                $this->readInternal(2);
                break;
            case TarsType::FLOAT:
            case TarsType::INT32:
                $this->readInternal(4);
                break;
            case TarsType::DOUBLE:
            case TarsType::INT64:
                $this->readInternal(8);
                break;
            case TarsType::STRING1:
            case TarsType::STRING4:
                $this->readStringInternal($type);
                break;
            case TarsType::STRUCT_BEGIN:
                while (true) {
                    $fieldType = $this->matchTag(null);
                    if (TarsType::STRUCT_END !== $fieldType) {
                        break;
                    }
                    $this->skipField($fieldType);
                }
                break;
            case TarsType::MAP:
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $keyType = $this->matchTag(0);
                    $this->skipField($keyType);
                    $valueType = $this->matchTag(1);
                    $this->skipField($valueType);
                }
                break;
            case TarsType::VECTOR:
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $itemType = $this->matchTag(0);
                    $this->skipField($itemType);
                }
                break;
            case TarsType::SIMPLE_LIST:
                $this->match(0, TarsType::INT8, true);
                $len = $this->readInt32(0, true);
                $this->readInternal($len);
                break;
            default:
                throw TarsException::typeNotMatch("Unknown type $type");
        }
    }

    private function readInt8Internal(int $type): int
    {
        if (TarsType::ZERO === $type) {
            return 0;
        }

        return ord($this->readInternal(1));
    }

    private function readInt16Internal(int $type): int
    {
        if (TarsType::INT16 === $type) {
            $unpack = unpack('n', $this->readInternal(2));
            if (false === $unpack) {
                throw TarsException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt8Internal($type);
    }

    private function readInt32Internal(int $type): int
    {
        if (TarsType::INT32 === $type) {
            $unpack = unpack('N', $this->readInternal(4));
            if (false === $unpack) {
                throw TarsException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt16Internal($type);
    }

    private function readInt64Internal(int $type): int
    {
        if (TarsType::INT64 === $type) {
            $unpack = unpack('J', $this->readInternal(8));
            if (false === $unpack) {
                throw TarsException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt32Internal($type);
    }

    private function readStringInternal(int $type): string
    {
        if (TarsType::STRING1 === $type) {
            $len = ord($this->readInternal(1));
        } else {
            $len = $this->readInt32Internal(TarsType::INT32);
        }

        if (0 === $len) {
            return '';
        }

        return $this->readInternal($len);
    }

    /**
     * {@inheritDoc}
     */
    public function read(int $tag, bool $require, Type $type)
    {
        if ($type->isPrimitive()) {
            switch ($type->asPrimitiveType()->getPhpType()) {
                case PrimitiveType::CHAR:
                    return $this->readChar($tag, $require);
                case PrimitiveType::BOOL:
                    return $this->readBool($tag, $require);
                case PrimitiveType::INT8:
                    return $this->readInt8($tag, $require);
                case PrimitiveType::UINT8:
                case PrimitiveType::SHORT:
                    return $this->readInt16($tag, $require);
                case PrimitiveType::UINT16:
                case PrimitiveType::INT32:
                    return $this->readInt32($tag, $require);
                case PrimitiveType::UINT32:
                case PrimitiveType::INT64:
                    return $this->readInt64($tag, $require);
                case PrimitiveType::FLOAT:
                    return $this->readFloat($tag, $require);
                case PrimitiveType::DOUBLE:
                    return $this->readDouble($tag, $require);
                case PrimitiveType::STRING:
                    return $this->readString($tag, $require);
                default:
                    throw TarsException::typeNotMatch('unknown primitive type '.$type);
            }
        } elseif ($type->isEnum()) {
            $value = $this->readInt64($tag, $require);
            if (null === $value) {
                return null;
            }

            return $type->asEnumType()->createEnum($value);
        } elseif ($type->isStruct()) {
            return $this->readStruct($tag, $require, $type->asStructType());
        } elseif ($type->isVector()) {
            return $this->readVector($tag, $require, $type->asVectorType());
        } elseif ($type->isMap()) {
            return $this->readMap($tag, $require, $type->asMapType());
        } else {
            throw TarsException::typeNotMatch('unknown type '.$type);
        }
    }

    public function readBool(int $tag, bool $require): ?bool
    {
        $value = $this->readInt8($tag, $require);
        if (null === $value) {
            return null;
        }

        return (bool) $value;
    }

    public function readChar(int $tag, bool $require): ?string
    {
        $value = $this->readInt8($tag, $require);
        if (null === $value) {
            return null;
        }

        return chr($value);
    }

    public function readInt8(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, TarsType::INT8, $require);
        if (null === $type) {
            return null;
        }

        return $this->readInt8Internal($type);
    }

    public function readInt16(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, TarsType::INT16, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt16Internal($type);
    }

    public function readInt32(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, TarsType::INT32, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt32Internal($type);
    }

    public function readInt64(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, TarsType::INT64, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt64Internal($type);
    }

    public function readFloat(int $tag, bool $require): ?float
    {
        $type = $this->match($tag, TarsType::FLOAT, $require);
        if (null === $type) {
            return null;
        }
        if (TarsType::ZERO === $type) {
            return 0;
        }
        $unpack = unpack('G', $this->readInternal(4));
        if (false === $unpack) {
            throw TarsException::outOfRange();
        }

        return $unpack[1];
    }

    public function readDouble(int $tag, bool $require): ?float
    {
        $type = $this->match($tag, TarsType::DOUBLE, $require);
        if (null === $type) {
            return null;
        }
        if (TarsType::ZERO === $type) {
            return 0;
        }
        $unpack = unpack('E', $this->readInternal(8));
        if (false === $unpack) {
            throw TarsException::outOfRange();
        }

        return $unpack[1];
    }

    public function readUInt8(int $tag, bool $require): ?int
    {
        return $this->readInt16($tag, $require);
    }

    public function readUInt16(int $tag, bool $require): ?int
    {
        return $this->readInt32($tag, $require);
    }

    public function readUInt32(int $tag, bool $require): ?int
    {
        return $this->readInt64($tag, $require);
    }

    public function readString(int $tag, bool $require): ?string
    {
        $type = $this->match($tag, TarsType::STRING4, $require);
        if (null === $type) {
            return null;
        }

        return $this->readStringInternal($type);
    }

    public function readStruct(int $tag, bool $require, StructType $structType): ?object
    {
        $type = $this->match($tag, TarsType::STRUCT_BEGIN, $require);
        if (null === $type) {
            return null;
        }
        $className = $structType->getClassName();
        $obj = new $className();
        foreach ($structType->getFields() as $field) {
            $obj->{$field->getName()} = $this->read($field->getTag(), $field->isRequired(), $field->getType());
        }
        $this->match(0, TarsType::STRUCT_END, true);

        return $obj;
    }

    /**
     * {@inheritDoc}
     */
    public function readVector(int $tag, bool $require, VectorType $vectorType)
    {
        $type = $this->match($tag, TarsType::VECTOR, $require);
        if (null === $type) {
            return null;
        }
        if (TarsType::SIMPLE_LIST === $type) {
            $this->match(0, TarsType::INT8, true);
            $len = $this->readInt32(0, true);

            if (0 === $len) {
                return '';
            }

            return $this->readInternal($len);
        }
        $len = $this->readInt32(0, true);
        $array = [];
        for ($i = 0; $i < $len; ++$i) {
            $array[] = $this->read(0, true, $vectorType->getSubType());
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function readMap(int $tag, bool $require, MapType $mapType)
    {
        $type = $this->match($tag, TarsType::MAP, $require);
        if (null === $type) {
            return null;
        }
        $len = $this->readInt32(0, true);
        if ($mapType->getKeyType()->isPrimitive()) {
            $map = [];
            for ($i = 0; $i < $len; ++$i) {
                $key = $this->read(0, true, $mapType->getKeyType());
                $map[$key] = $this->read(1, true, $mapType->getValueType());
            }
        } else {
            $map = new StructMap();
            for ($i = 0; $i < $len; ++$i) {
                $map->put(
                    $this->read(0, true, $mapType->getKeyType()),
                    $this->read(1, true, $mapType->getValueType())
                );
            }
        }

        return $map;
    }

    /**
     * @return mixed
     */
    public static function unpack(Type $type, string $data)
    {
        $is = new TarsInputStream($data);

        return $is->read(0, true, $type);
    }

    public function tokenize(): array
    {
        $tokens = [];
        while (true) {
            $token = $this->nextToken();
            if (null === $token) {
                break;
            }
            $tokens[] = $token;
        }

        return $tokens;
    }

    private function nextToken(): ?array
    {
        if (!$this->readHead($tag, $type, false)) {
            return null;
        }
        $token = [$tag, TarsType::fromValue($type)];
        switch ($type) {
            case TarsType::ZERO:
            case TarsType::INT8:
            case TarsType::INT16:
            case TarsType::INT32:
            case TarsType::INT64:
                $token[] = $this->readInt64Internal($type);
                break;
            case TarsType::FLOAT:
                $token[] = unpack('G', $this->readInternal(4))[1];
                break;
            case TarsType::DOUBLE:
                $token[] = unpack('J', $this->readInternal(8))[1];
                break;
            case TarsType::STRING1:
            case TarsType::STRING4:
                $token[] = $this->readStringInternal($type);
                break;
            case TarsType::STRUCT_END:
                $token[] = null;
                break;
            case TarsType::STRUCT_BEGIN:
                $fields = [];
                while (true) {
                    $field = $this->nextToken();
                    if (TarsType::STRUCT_END === $field[0]) {
                        break;
                    }
                    $fields[] = $field;
                }
                $token[] = $fields;
                break;
            case TarsType::MAP:
                $map = [];
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $map[] = [
                        'key' => $this->nextToken(),
                        'value' => $this->nextToken(),
                    ];
                }
                $token[] = $map;
                break;
            case TarsType::VECTOR:
                $list = [];
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $list[] = $this->nextToken();
                }
                $token[] = $list;
                break;
            case TarsType::SIMPLE_LIST:
                $this->match(0, TarsType::INT8, true);
                $len = $this->readInt32(0, true);
                $token[] = $this->readInternal($len);
                break;
            default:
                throw TarsException::typeNotMatch("Unknown type $type");
        }

        return $token;
    }
}
