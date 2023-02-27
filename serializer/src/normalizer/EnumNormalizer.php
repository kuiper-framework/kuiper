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

use InvalidArgumentException;
use kuiper\helper\Enum;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\NormalizerInterface;

class EnumNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): mixed
    {
        if ($object instanceof Enum) {
            return $object->name();
        }

        throw new InvalidArgumentException('Expected Enum object, got '.gettype($object));
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Expected string, got '.gettype($data));
        }

        return call_user_func([$className, 'fromName'], $data);
    }
}
