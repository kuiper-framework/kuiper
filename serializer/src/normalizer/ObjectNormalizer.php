<?php

declare(strict_types=1);

namespace kuiper\serializer\normalizer;

use kuiper\serializer\ClassMetadataFactory;
use kuiper\serializer\NormalizerInterface;

class ObjectNormalizer implements NormalizerInterface
{
    /**
     * @var ClassMetadataFactory
     */
    private $classMetadataFactory;

    /**
     * @var NormalizerInterface
     */
    private $serializer;

    /**
     * ObjectNormalizer constructor.
     */
    public function __construct(ClassMetadataFactory $classMetadataFactory, NormalizerInterface $serializer)
    {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        $metadata = $this->classMetadataFactory->create(get_class($object));
        $data = [];
        foreach ($metadata->getGetters() as $getter) {
            $data[$getter->getSerializeName()] = $this->serializer->normalize($getter->getValue($object));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $className)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Expected array, got '.gettype($data));
        }
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Expected class name, got '.gettype($className));
        }
        $metadata = $this->classMetadataFactory->create($className);
        $class = new \ReflectionClass($className);
        $object = $class->newInstanceWithoutConstructor();
        foreach ($metadata->getSetters() as $setter) {
            if (!isset($data[$setter->getSerializeName()])) {
                continue;
            }
            $setter->setValue($object, $this->serializer->denormalize($data[$setter->getSerializeName()], $setter->getType()));
        }

        return $object;
    }
}
