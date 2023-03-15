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

use BackedEnum;
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
        if ($object instanceof BackedEnum) {
            return $object->value;
        }
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
        if (is_a($class, BackedEnum::class, true)) {
            if (is_string($data)) {
                return $class::tryFrom($data) ?? EnumHelper::tryFromName($class, $data);
            }
            if (is_int($data)) {
                return $class::tryFrom($data);
            }
            throw new InvalidArgumentException('Expected string or int, got '.gettype($data));
        }

        return EnumHelper::tryFromName($class, $data);
    }
}
