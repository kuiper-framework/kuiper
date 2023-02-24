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

namespace kuiper\db\metadata;

use kuiper\reflection\ReflectionTypeInterface;

interface ColumnInterface
{
    /**
     * Gets the column name
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the php type
     * @return ReflectionTypeInterface
     */
    public function getType(): ReflectionTypeInterface;

    /**
     * Gets the object property path
     * @return string
     */
    public function getPropertyPath(): string;

    /**
     * Gets the property meta data
     * @return MetaModelProperty
     */
    public function getProperty(): MetaModelProperty;
}
