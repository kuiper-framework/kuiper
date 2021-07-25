<?php

declare(strict_types=1);

namespace kuiper\tars\stream;

use kuiper\tars\exception\TarsStreamException;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use kuiper\tars\type\StructMap;
use kuiper\tars\type\StructType;
use kuiper\tars\type\Type;
use kuiper\tars\type\VectorType;

class TarsInputStream implements TarsInputStreamInterface
{
    private const TYPE_ALIAS = [
        Type::INT8 => [Type::ZERO],
        Type::INT16 => [Type::INT8, Type::ZERO],
        Type::INT32 => [Type::INT16, Type::INT8, Type::ZERO],
        Type::INT64 => [Type::INT32, Type::INT16, Type::INT8, Type::ZERO],
        Type::FLOAT => [Type::ZERO],
        Type::DOUBLE => [Type::ZERO],
        Type::STRING4 => [Type::STRING1],
        Type::VECTOR => [Type::SIMPLE_LIST],
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
            throw TarsStreamException::streamLenError();
        }

        return $char;
    }

    private function readHead(?int &$tag, ?int &$type, bool $require): bool
    {
        $header = fread($this->buffer, 1);
        if (false === $header || '' === $header) {
            if ($require) {
                throw TarsStreamException::streamLenError();
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
            throw TarsStreamException::tagNotMatch();
        }
        if (null !== $tag && $nextTag !== $tag) {
            throw TarsStreamException::tagNotMatch();
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

    /**
     * @param int      $tag     the tag value
     * @param int|null $type    the expected type
     * @param bool     $require
     *
     * @return int|null the real type
     *
     * @throws TarsStreamException
     */
    private function match(int $tag, ?int $type, bool $require): ?int
    {
        if (!$this->readHead($nextTag, $nextType, $require)) {
            return null;
        }
        if (Type::STRUCT_END === $nextType) {
            if (Type::STRUCT_END === $type || !$require) {
                return null;
            }
            throw TarsStreamException::typeNotMatch('expected struct end, got '.self::getTypeName($type));
        }
        if (Type::STRUCT_END !== $type) {
            if ($nextTag === $tag) {
                if ($nextType !== $type && !in_array($nextType, self::TYPE_ALIAS[$type] ?? [], true)) {
                    throw TarsStreamException::typeNotMatch("Expected type $type, got $nextType");
                }

                return $nextType;
            }
            if ($nextTag > $tag) {
                if ($require) {
                    throw TarsStreamException::tagNotMatch();
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
            case Type::ZERO:
                break;
            case Type::INT8:
                $this->readInternal(1);
                break;
            case Type::INT16:
                $this->readInternal(2);
                break;
            case Type::FLOAT:
            case Type::INT32:
                $this->readInternal(4);
                break;
            case Type::DOUBLE:
            case Type::INT64:
                $this->readInternal(8);
                break;
            case Type::STRING1:
            case Type::STRING4:
                $this->readStringInternal($type);
                break;
            case Type::STRUCT_BEGIN:
                while (true) {
                    $fieldType = $this->matchTag(null);
                    if (Type::STRUCT_END !== $fieldType) {
                        break;
                    }
                    $this->skipField($fieldType);
                }
                break;
            case Type::MAP:
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $keyType = $this->matchTag(0);
                    $this->skipField($keyType);
                    $valueType = $this->matchTag(1);
                    $this->skipField($valueType);
                }
                break;
            case Type::VECTOR:
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $itemType = $this->matchTag(0);
                    $this->skipField($itemType);
                }
                break;
            case Type::SIMPLE_LIST:
                $this->match(0, Type::INT8, true);
                $len = $this->readInt32(0, true);
                $this->readInternal($len);
                break;
            default:
                throw TarsStreamException::typeNotMatch("Unknown type $type");
        }
    }

    /**
     * @throws TarsStreamException
     */
    private function readInt8Internal(int $type): int
    {
        if (Type::ZERO === $type) {
            return 0;
        }

        return ord($this->readInternal(1));
    }

    /**
     * @throws TarsStreamException
     */
    private function readInt16Internal(int $type): int
    {
        if (Type::INT16 === $type) {
            $unpack = unpack('n', $this->readInternal(2));
            if (false === $unpack) {
                throw TarsStreamException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt8Internal($type);
    }

    /**
     * @throws TarsStreamException
     */
    private function readInt32Internal(int $type): int
    {
        if (Type::INT32 === $type) {
            $unpack = unpack('N', $this->readInternal(4));
            if (false === $unpack) {
                throw TarsStreamException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt16Internal($type);
    }

    /**
     * @throws TarsStreamException
     */
    private function readInt64Internal(int $type): int
    {
        if (Type::INT64 === $type) {
            $unpack = unpack('J', $this->readInternal(8));
            if (false === $unpack) {
                throw TarsStreamException::outOfRange();
            }

            return $unpack[1];
        }

        return $this->readInt32Internal($type);
    }

    /**
     * @throws TarsStreamException
     */
    private function readStringInternal(int $type): string
    {
        if (Type::STRING1 === $type) {
            $len = ord($this->readInternal(1));
        } else {
            $len = $this->readInt32Internal(Type::INT32);
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
            switch ($type->asPrimitiveType()->getTarsType()) {
                case Type::INT8:
                    $value = $this->readInt8($tag, $require);
                    $phpType = $type->asPrimitiveType()->getPhpType();
                    if (PrimitiveType::BOOL === $phpType) {
                        return (bool) $value;
                    }
                    if (PrimitiveType::CHAR === $phpType) {
                        return chr($value);
                    }

                    return $value;
                case Type::INT16:
                    return $this->readInt16($tag, $require);
                case Type::INT32:
                    return $this->readInt32($tag, $require);
                case Type::INT64:
                    return $this->readInt64($tag, $require);
                case Type::FLOAT:
                    return $this->readFloat($tag, $require);
                case Type::DOUBLE:
                    return $this->readDouble($tag, $require);
                case Type::STRING4:
                    return $this->readString($tag, $require);
                default:
                    throw TarsStreamException::typeNotMatch('unknown primitive type '.$type);
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
            throw TarsStreamException::typeNotMatch('unknown type '.$type);
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
        $type = $this->match($tag, Type::INT8, $require);
        if (null === $type) {
            return null;
        }

        return $this->readInt8Internal($type);
    }

    public function readInt16(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, Type::INT16, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt16Internal($type);
    }

    public function readInt32(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, Type::INT32, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt32Internal($type);
    }

    public function readInt64(int $tag, bool $require): ?int
    {
        $type = $this->match($tag, Type::INT64, $require);

        if (null === $type) {
            return null;
        }

        return $this->readInt64Internal($type);
    }

    public function readFloat(int $tag, bool $require): ?float
    {
        $type = $this->match($tag, Type::FLOAT, $require);
        if (null === $type) {
            return null;
        }
        if (Type::ZERO === $type) {
            return 0;
        }
        $unpack = unpack('G', $this->readInternal(4));
        if (false === $unpack) {
            throw TarsStreamException::outOfRange();
        }

        return $unpack[1];
    }

    public function readDouble(int $tag, bool $require): ?float
    {
        $type = $this->match($tag, Type::DOUBLE, $require);
        if (null === $type) {
            return null;
        }
        if (Type::ZERO === $type) {
            return 0;
        }
        $unpack = unpack('E', $this->readInternal(8));
        if (false === $unpack) {
            throw TarsStreamException::outOfRange();
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
        $type = $this->match($tag, Type::STRING4, $require);
        if (null === $type) {
            return null;
        }

        return $this->readStringInternal($type);
    }

    public function readStruct(int $tag, bool $require, StructType $structType): ?object
    {
        $type = $this->match($tag, Type::STRUCT_BEGIN, $require);
        if (null === $type) {
            return null;
        }
        $className = $structType->getClassName();
        $obj = new $className();
        foreach ($structType->getFields() as $field) {
            /* @phpstan-ignore-next-line */
            $obj->{$field->getName()} = $this->read($field->getTag(), $field->isRequired(), $field->getType());
        }
        $this->match(0, Type::STRUCT_END, true);

        return $obj;
    }

    /**
     * {@inheritDoc}
     */
    public function readVector(int $tag, bool $require, VectorType $vectorType)
    {
        $type = $this->match($tag, Type::VECTOR, $require);
        if (null === $type) {
            return null;
        }
        if (Type::SIMPLE_LIST === $type) {
            $this->match(0, Type::INT8, true);
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
        $type = $this->match($tag, Type::MAP, $require);
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

    private static function getTypeName(int $type): string
    {
        static $typeNames;
        if (null === $typeNames) {
            $reflectionClass = new \ReflectionClass(Type::class);
            foreach ($reflectionClass->getConstants() as $name => $value) {
                $typeNames[$value] = $name;
            }
        }

        return $typeNames[$type] ?? '';
    }

    private function nextToken(): ?array
    {
        if (!$this->readHead($tag, $type, false)) {
            return null;
        }
        $token = [$tag, self::getTypeName($type)];
        switch ($type) {
            case Type::ZERO:
            case Type::INT8:
            case Type::INT16:
            case Type::INT32:
            case Type::INT64:
                $token[] = $this->readInt64Internal($type);
                break;
            case Type::FLOAT:
                $token[] = unpack('G', $this->readInternal(4))[1];
                break;
            case Type::DOUBLE:
                $token[] = unpack('J', $this->readInternal(8))[1];
                break;
            case Type::STRING1:
            case Type::STRING4:
                $token[] = $this->readStringInternal($type);
                break;
            case Type::STRUCT_END:
                $token[] = null;
                break;
            case Type::STRUCT_BEGIN:
                $fields = [];
                while (true) {
                    $field = $this->nextToken();
                    if (Type::STRUCT_END === $field[0]) {
                        break;
                    }
                    $fields[] = $field;
                }
                $token[] = $fields;
                break;
            case Type::MAP:
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
            case Type::VECTOR:
                $list = [];
                $len = $this->readInt32(0, true);
                for ($i = 0; $i < $len; ++$i) {
                    $list[] = $this->nextToken();
                }
                $token[] = $list;
                break;
            case Type::SIMPLE_LIST:
                $this->match(0, Type::INT8, true);
                $len = $this->readInt32(0, true);
                $token[] = $this->readInternal($len);
                break;
            default:
                throw TarsStreamException::typeNotMatch("Unknown type $type");
        }

        return $token;
    }
}
