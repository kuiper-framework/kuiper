<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

class SerializerRegistry
{
    /**
     * @var array
     */
    private $serializers;

    /**
     * SerializerRegistry constructor.
     */
    public function __construct()
    {
        $this->serializers = [
            'json' => new JsonSerializer(),
            'array' => new ArraySerializer(),
        ];
    }

    /**
     * @return bool
     */
    public function hasSerializer(string $name)
    {
        return isset($this->serializers[$name]);
    }

    /**
     * @return Serializer|null
     */
    public function getSerializer(string $name)
    {
        return isset($this->serializers[$name]) ? $this->serializers[$name] : null;
    }

    /**
     * @return $this
     */
    public function addSerializer(string $name, Serializer $serializer)
    {
        $this->serializers[$name] = $serializer;

        return $this;
    }
}
