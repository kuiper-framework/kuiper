<?php

namespace kuiper\serializer\normalizer;

use kuiper\helper\Enum;
use kuiper\serializer\NormalizerInterface;

class EnumNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        if ($object instanceof Enum) {
            return $object->name();
        } else {
            throw new \InvalidArgumentException('Expected Enum object, got '.gettype($object));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type = null)
    {
        if (is_array($data) && isset($data['name'])) {
            $data = $data['name'];
        }
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Expected string, got '.gettype($data));
        }
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Expected class Name, got '.gettype($type));
        }

        return call_user_func([$type, 'fromName'], $data);
    }
}
