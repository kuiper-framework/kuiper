<?php

namespace kuiper\serializer;

interface JsonSerializerInterface
{
    /**
     * Serializes data into json.
     *
     * @param array|object $data
     * @param int          $options option for json_encode
     *
     * @return string
     *
     * @throws exception\SerializeException
     */
    public function toJson($data, $options = 0);

    /**
     * Converts json to object.
     *
     * @param string        $jsonString
     * @param string|object $type
     *
     * @return mixed
     *
     * @throws exception\SerializeException
     */
    public function fromJson($jsonString, $type);
}
