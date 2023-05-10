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
use kuiper\helper\EnumHelper;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\serializer\NormalizerInterface;
use UnitEnum;

class PhpEnumNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object): mixed
    {
        if ($object instanceof UnitEnum) {
            return $object->name;
        }

        throw new InvalidArgumentException('Expected Enum object, got '.gettype($object));
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed
    {
        /** @var class-string<UnitEnum> $class */
        $class = (string) $className;
        if (is_string($data)) {
            return EnumHelper::tryFromName($class, $data) ?? EnumHelper::tryFrom($class, $data);
        }

        return EnumHelper::tryFrom($class, $data);
    }
}
