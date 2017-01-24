<?php

namespace kuiper\serializer;

interface NormalizerInterface
{
    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * @param object|array $data
     *
     * @return array
     *
     * @throws exception\SerializeException
     */
    public function toArray($data);

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param array         $data
     * @param string|object $type
     *
     * @return object|array
     *
     * @throws \InvalidArgumentException
     * @throws exception\SerializeException
     * @throws exception\UnexpectedValueException
     */
    public function fromArray(array $data, $type);
}
