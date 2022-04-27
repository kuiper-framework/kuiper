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

class ReflectionMethodDocBlock implements ReflectionMethodDocBlockInterface
{
    /**
     * ReflectionMethodDocBlock constructor.
     *
     * @param ReflectionTypeInterface[] $parameterTypes
     */
    public function __construct(
        private \ReflectionMethod $method,
        private array $parameterTypes,
        private ReflectionTypeInterface $returnType)
    {
    }

    public function getMethod(): \ReflectionMethod
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameterTypes(): array
    {
        return $this->parameterTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function getReturnType(): ReflectionTypeInterface
    {
        return $this->returnType;
    }
}
