<?php

namespace kuiper\serializer;

use kuiper\reflection\ReflectionTypeInterface;

interface NormalizerInterface
{
    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * @param object|array $object
     *
     * @return array|string
     *
     * @throws exception\SerializeException
     */
    public function normalize($object);

    /**
     * Turn data back into an object of the given class.
     *
     * @param string|array                   $data
     * @param string|ReflectionTypeInterface $type
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws exception\SerializeException
     * @throws exception\UnexpectedValueException
     */
    public function denormalize($data, $type = null);
}
