<?php

declare(strict_types=1);

namespace kuiper\serializer;

interface JsonSerializerInterface
{
    /**
     * Serializes data into json.
     *
     * @param array|object $data
     * @param int          $options option for json_encode
     *
     * @throws exception\SerializeException
     */
    public function toJson($data, $options = 0): string;

    /**
     * Converts json to object.
     *
     * @param string|object $type
     *
     * @return mixed
     *
     * @throws exception\SerializeException
     */
    public function fromJson(string $jsonString, $type);
}
