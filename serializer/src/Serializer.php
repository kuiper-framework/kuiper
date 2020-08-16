<?php

declare(strict_types=1);

namespace kuiper\serializer;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ArrayType;
use kuiper\reflection\type\CompositeType;
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
    private $objectNormalizer;

    /**
     * Note：
     * 注册的类型必须内处理所有子类的序列化。比如 Enum 的 Normalizer 必须能处理所有 Enum 子类的序列化.
     *
     * @var NormalizerInterface[]
     */
    private $normalizers = [];

    public function __construct(AnnotationReaderInterface $reader, DocReaderInterface $docReader, array $normalizers = [])
    {
        $classMetadataFactory = new ClassMetadataFactory($reader, $docReader);
        $this->objectNormalizer = new ObjectNormalizer($classMetadataFactory, $this);
        foreach ($normalizers as $className => $normalizer) {
            $this->addObjectNormalizer($className, $normalizer);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($object, $options = 0): string
    {
        return json_encode($this->normalize($object), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function fromJson(string $jsonString, $type)
    {
        return $this->denormalize(self::decodeJson($jsonString), $type);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        if (is_array($object)) {
            $ret = [];
            foreach ($object as $key => $val) {
                $ret[$key] = $this->normalize($val);
            }

            return $ret;
        } elseif (is_object($object)) {
            return $this->normalizeObject($object);
        } else {
            return $object;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $className)
    {
        if ($className instanceof ReflectionTypeInterface) {
            return $this->toType($data, $className);
        } elseif (is_string($className)) {
            return $this->toType($data, ReflectionType::parse($className));
        } else {
            throw new \InvalidArgumentException('Parameter type expects class name or object, got '.gettype($className));
        }
    }

    /**
     * Converts value to the type.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function toType($value, ReflectionTypeInterface $type)
    {
        if (!isset($value)) {
            // check type nullable?
            return null;
        }
        if ($type->isClass()) {
            $className = $type->getName();
            if (!class_exists($className)) {
                throw new SerializeException("Class '$className' does not exist");
            }

            return $this->denormalizeObject($value, $className);
        } elseif ($type->isComposite()) {
            /** @var CompositeType $type */
            foreach ($type->getTypes() as $subtype) {
                if (($subtype->isArray() && is_array($value)) || $subtype->isValid($value)) {
                    return $this->toType($value, $subtype);
                }
            }
            throw new UnexpectedValueException("Expects '$type', got ".ReflectionType::describe($value));
        } elseif ($type->isArray()) {
            /* @var ArrayType $type */
            if (!is_array($value)) {
                throw new UnexpectedValueException('Expects array, got '.ReflectionType::describe($value));
            }

            return $this->toArrayType($value, $type->getValueType(), $type->getDimension());
        } elseif ($type->isScalar() || $type->isValid($value)) {
            return $type->sanitize($value);
        } else {
            throw new UnexpectedValueException("Expects '$type', got ".ReflectionType::describe($value));
        }
    }

    private function toArrayType(array $value, ReflectionTypeInterface $valueType, int $dimension)
    {
        $result = [];
        if (1 === $dimension) {
            foreach ($value as $key => $item) {
                $result[$key] = $this->toType($item, $valueType);
            }
        } else {
            foreach ($value as $key => $item) {
                $result[$key] = $this->toArrayType($item, $valueType, $dimension - 1);
            }
        }

        return $result;
    }

    private function normalizeObject($object)
    {
        if ($object instanceof \JsonSerializable) {
            return $object->jsonSerialize();
        }
        foreach ($this->normalizers as $className => $normalizer) {
            if ($object instanceof $className) {
                return $normalizer->normalize($object);
            }
        }

        return $this->objectNormalizer->normalize($object);
    }

    private function denormalizeObject($data, $className)
    {
        foreach ($this->normalizers as $typeClass => $normalizer) {
            if (is_a($className, $typeClass, true)) {
                return $normalizer->denormalize($data, $className);
            }
        }

        return $this->objectNormalizer->denormalize($data, $className);
    }

    public static function decodeJson($json)
    {
        $data = json_decode($json, true);
        if (false === $data) {
            throw new MalformedJsonException(sprintf("%s, json string was '%s'", json_last_error_msg(), $json));
        }

        return $data;
    }

    public function addObjectNormalizer(string $className, NormalizerInterface $normalizer)
    {
        $this->normalizers[$className] = $normalizer;

        return $this;
    }
}
