<?php

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
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
