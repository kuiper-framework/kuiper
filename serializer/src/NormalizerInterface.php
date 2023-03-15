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

namespace kuiper\serializer;

use InvalidArgumentException;
use kuiper\reflection\ReflectionTypeInterface;

interface NormalizerInterface
{
    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * @param object|array $object
     *
     * @return mixed
     *
     * @throws exception\SerializeException
     */
    public function normalize(mixed $object): mixed;

    /**
     * Turn data back into an object of the given class.
     *
     * @param mixed                          $data
     * @param string|ReflectionTypeInterface $className
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     * @throws exception\SerializeException
     * @throws exception\UnexpectedValueException
     */
    public function denormalize(mixed $data, string|ReflectionTypeInterface $className): mixed;
}
