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

interface ReflectionDocBlockFactoryInterface
{
    /**
     * @param \ReflectionProperty $property
     *
     * @return ReflectionPropertyDocBlockInterface
     */
    public function createPropertyDocBlock(\ReflectionProperty $property): ReflectionPropertyDocBlockInterface;

    /**
     * @param \ReflectionMethod $method
     *
     * @return ReflectionMethodDocBlockInterface
     */
    public function createMethodDocBlock(\ReflectionMethod $method): ReflectionMethodDocBlockInterface;
}
