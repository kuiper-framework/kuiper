<?php

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
     * @var string
     */
    private $classNameKey = '@class';

    /**
     * ObjectNormalizer constructor.
     *
     * @param ClassMetadataFactory $classMetadataFactory
     * @param NormalizerInterface  $serializer
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
        if ($object instanceof \JsonSerializable) {
            $data = $object->jsonSerialize();
        } else {
            $metadata = $this->classMetadataFactory->create(get_class($object));
            $data = [];
            foreach ($metadata->getGetters() as $getter) {
                $data[$getter->getSerializeName()] = $this->serializer->normalize($getter->getValue($object));
            }
        }

        $data[$this->classNameKey] = get_class($object);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $className = null)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Expected array, got '.gettype($data));
        }
        if (isset($data[$this->classNameKey])) {
            $className = $data[$this->classNameKey];
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
