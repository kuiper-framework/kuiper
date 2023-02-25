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

/**
 * @extends \ArrayIterator<int, StructMapEntry>
 */
class StructMap extends \ArrayIterator implements \JsonSerializable
{
    /**
     * @param object $key
     * @param object $value
     */
    public function put($key, $value): void
    {
        $this->append(new StructMapEntry($key, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
