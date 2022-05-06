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

namespace kuiper\serializer\normalizer;

use InvalidArgumentException;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\ClassMetadataFactory;
use kuiper\serializer\NormalizerInterface;
use ReflectionClass;

class ObjectNormalizer implements NormalizerInterface
{
    /**
     * ObjectNormalizer constructor.
     */
    public function __construct(
        private readonly ClassMetadataFactory $classMetadataFactory,
        private readonly NormalizerInterface $serializer)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): array|string
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
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Expected array, got '.gettype($data));
        }
        if (!is_string($className)) {
            throw new InvalidArgumentException('Expected class name, got '.gettype($className));
        }
        $metadata = $this->classMetadataFactory->create($className);
        $class = new ReflectionClass($className);
        $object = $class->newInstanceWithoutConstructor();
        foreach ($metadata->getSetters() as $setter) {
            foreach ([$setter->getSerializeName(),
                         Text::snakeCase($setter->getSerializeName()),
                         Text::snakeCase($setter->getSerializeName(), '-'), ] as $key) {
                if (isset($data[$key])) {
                    $setter->setValue($object, $this->serializer->denormalize($data[$key], $setter->getType()));
                    break;
                }
            }
        }

        return $object;
    }
}
