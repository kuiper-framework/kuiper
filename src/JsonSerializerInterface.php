<?php
namespace kuiper\serializer;

interface JsonSerializerInterface
{
    /**
     * Serializes data into json
     *
     * @param object $object
     * @return string
     */
    public function toJson($object);

    /**
     * Converts json to object
     *
     * @param string $jsonString
     * @param string|object $className
     * @return object
     */
    public function fromJson($jsonString, $className);
}
