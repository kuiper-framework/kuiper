<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\serializer\normalizer;

use kuiper\serializer\NormalizerInterface;

class DateTimeNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object)
    {
        if ($object instanceof \DateTimeInterface) {
            return $object->format(\DateTimeInterface::RFC3339);
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
        }

        if (isset($data['date'], $data['timezone']) && is_array($data)) {
            // \DateTime array
            return new \DateTime($data['date'], new \DateTimeZone($data['timezone']));
        }
        throw new \InvalidArgumentException('not valid date');
    }
}
