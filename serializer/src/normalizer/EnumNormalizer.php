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
    public function denormalize($exception, $className)
    {
        if (!is_string($exception)) {
            throw new \InvalidArgumentException('Expected string, got '.gettype($exception));
        }

        return call_user_func([$className, 'fromName'], $exception);
    }
}
