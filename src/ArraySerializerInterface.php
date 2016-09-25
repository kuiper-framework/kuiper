<?php
namespace kuiper\serializer;

interface ArraySerializerInterface
{
    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * @param object $object
     * @return array
     */
    public function toArray($object);

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param array $data
     * @param string|object $className
     * @return object
     */
    public function fromArray(array $data, $className);
}
