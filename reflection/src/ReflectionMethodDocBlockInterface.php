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

namespace kuiper\reflection;

interface ReflectionMethodDocBlockInterface extends ReflectionDocBlockInterface
{
    /**
     * Parses the docblock of the method to get all parameters type.
     *
     * @return array<string, ReflectionTypeInterface> the key of array is parameter name
     *
     * @throws exception\ClassNotFoundException
     */
    public function getParameterTypes(): array;

    /**
     * Parses the docblock of the method to get return type.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getReturnType(): ReflectionTypeInterface;
}
