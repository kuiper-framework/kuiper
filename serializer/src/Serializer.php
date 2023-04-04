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

namespace kuiper\serializer;

use JsonException;
use JsonSerializable;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\CompositeType;
use kuiper\reflection\type\MapType;
use kuiper\serializer\exception\MalformedJsonException;
use kuiper\serializer\exception\SerializeException;
use kuiper\serializer\exception\UnexpectedValueException;
use kuiper\serializer\normalizer\ObjectNormalizer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Serializer implements NormalizerInterface, JsonSerializerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ObjectNormalizer
     */
    private readonly ObjectNormalizer $objectNormalizer;

    /**
     * Note：
     * 注册的类型必须内处理所有子类的序列化。比如 Enum 的 Normalizer 必须能处理所有 Enum 子类的序列化.
     *
     * @var NormalizerInterface[]
     */
    private array $normalizers = [];

    public function __construct(?ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory = null, array $normalizers = [])
    {
        $classMetadataFactory = new ClassMetadataFactory($reflectionDocBlockFactory ?? ReflectionDocBlockFactory::getInstance());
        $this->objectNormalizer = new ObjectNormalizer($classMetadataFactory, $this);
        foreach ($normalizers as $className => $normalizer) {
            $this->addObjectNormalizer($className, $normalizer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($data, int $options = 0): string
    {
        return json_encode($this->normalize($data), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function fromJson(string $jsonString, string|object $type): mixed
    {
        return $this->denormalize(self::decodeJson($jsonString), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): mixed
    {
        if (is_array($object)) {
            $ret = [];
            foreach ($object as $key => $val) {
                $ret[$key] = $this->normalize($val);
            }

            return $ret;
        }

        if (is_object($object)) {
            return $this->normalizeObject($object);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        if ($className instanceof ReflectionTypeInterface) {
            return $this->toType($data, $className);
        }

        return $this->toType($data, ReflectionType::parse($className));
    }

    /**
     * Converts value to the type.
     */
    private function toType(mixed $value, ReflectionTypeInterface $type): mixed
    {
        if (!isset($value)) {
            // check type nullable?
            return null;
        }
        if ($type->isClass()) {
            $className = $type->getName();
            if (!class_exists($className) && !interface_exists($className)) {
                throw new SerializeException("Class '$className' does not exist");
            }

            return $this->denormalizeObject($value, $className);
        }

        /** @var CompositeType|ArrayType $type */
        if ($type->isComposite()) {
            foreach ($type->getTypes() as $subtype) {
                if (($subtype->isArray() && is_array($value)) || $subtype->isValid($value)) {
                    return $this->toType($value, $subtype);
                }
            }
            throw new UnexpectedValueException("Expects '$type', got ".ReflectionType::describe($value));
        }

        if ($type->isArray()) {
            if (!is_array($value)) {
                throw new UnexpectedValueException('Expects array, got '.ReflectionType::describe($value));
            }
            if ($type instanceof ArrayType) {
                return $this->toArrayType($value, $type->getValueType(), $type->getDimension());
            }
            if ($type instanceof MapType) {
                $result = [];
                foreach ($value as $key => $item) {
                    $result[$key] = $this->toType($item, $type->getValueType());
                }

                return $result;
            }
        }

        if ($type->isScalar() || $type->isValid($value)) {
            return $type->sanitize($value);
        }

        throw new UnexpectedValueException("Expects '$type', got ".ReflectionType::describe($value));
    }

    private function toArrayType(array $value, ReflectionTypeInterface $valueType, int $dimension): array
    {
        $result = [];
        if (1 === $dimension) {
            foreach ($value as $item) {
                $result[] = $this->toType($item, $valueType);
            }
        } else {
            foreach ($value as $item) {
                $result[] = $this->toArrayType($item, $valueType, $dimension - 1);
            }
        }

        return $result;
    }

    private function normalizeObject(object $object): mixed
    {
        if ($object instanceof JsonSerializable) {
            return $object->jsonSerialize();
        }
        foreach ($this->normalizers as $className => $normalizer) {
            if ($object instanceof $className) {
                return $normalizer->normalize($object);
            }
        }

        return $this->objectNormalizer->normalize($object);
    }

    private function denormalizeObject(mixed $data, string $className): mixed
    {
        foreach ($this->normalizers as $typeClass => $normalizer) {
            if (is_a($className, $typeClass, true)) {
                return $normalizer->denormalize($data, $className);
            }
        }

        return $this->objectNormalizer->denormalize($data, $className);
    }

    /**
     * @throws JsonException
     */
    public static function decodeJson(string $json): mixed
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (false === $data) {
            throw new MalformedJsonException(sprintf("%s, json string was '%s'", json_last_error_msg(), $json));
        }

        return $data;
    }

    public function addObjectNormalizer(string $className, NormalizerInterface $normalizer): void
    {
        $this->normalizers[$className] = $normalizer;
    }
}
