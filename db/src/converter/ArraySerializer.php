<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\orm\ColumnMetadata;

class ArraySerializer implements Serializer
{
    /**
     * @var string
     */
    private $delimiter;

    /**
     * ArraySerializer constructor.
     *
     * @param string $delimiter
     */
    public function __construct($delimiter = '|')
    {
        $this->delimiter = $delimiter;
    }

    public function serialize($value, ColumnMetadata $column)
    {
        return is_array($value) ? implode($this->delimiter, $value) : $value;
    }

    public function unserialize($data, ColumnMetadata $column)
    {
        return is_string($data) && !empty($data) ? explode($this->delimiter, $data) : [];
    }
}
