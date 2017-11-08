<?php

namespace kuiper\serializer\normalizer;

use kuiper\serializer\NormalizerInterface;

class DateTimeNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        if ($object instanceof \DateTime) {
            return $object->format(\DateTime::RFC3339);
        } else {
            throw new \InvalidArgumentException('Expected DateTime object, got '.gettype($object));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $className)
    {
        if (is_string($data)) {
            return new \DateTime($data);
        } elseif (is_array($data) && isset($data['date']) && isset($data['timezone'])) {
            // \DateTime array
            return new \DateTime($data['date'], new \DateTimeZone($data['timezone']));
        }
    }
}
