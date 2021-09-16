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

class ReflectionPropertyDocBlock implements ReflectionPropertyDocBlockInterface
{
    /**
     * @var \ReflectionProperty
     */
    private $property;

    /**
     * @var ReflectionTypeInterface
     */
    private $type;

    /**
     * ReflectionPropertyDocBlockImpl constructor.
     */
    public function __construct(\ReflectionProperty $property, ReflectionTypeInterface $type)
    {
        $this->property = $property;
        $this->type = $type;
    }

    public function getProperty(): \ReflectionProperty
    {
        return $this->property;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): ReflectionTypeInterface
    {
        return $this->type;
    }
}
