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

interface ReflectionPropertyDocBlockInterface extends ReflectionDocBlockInterface
{
    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @throws exception\ClassNotFoundException
     */
    public function getType(): ReflectionTypeInterface;
}
