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

namespace kuiper\tars\type;

use JsonSerializable;

class StructMapEntry implements JsonSerializable
{
    public function __construct(private readonly mixed $key, private readonly mixed $value)
    {
    }

    /**
     * @return object
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return object
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
