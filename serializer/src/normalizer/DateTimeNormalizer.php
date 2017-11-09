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
    public function denormalize($exception, $className)
    {
        if (is_string($exception)) {
            return new \DateTime($exception);
        } elseif (is_array($exception) && isset($exception['date']) && isset($exception['timezone'])) {
            // \DateTime array
            return new \DateTime($exception['date'], new \DateTimeZone($exception['timezone']));
        }
    }
}
