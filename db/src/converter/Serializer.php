<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\orm\ColumnMetadata;

interface Serializer
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value, ColumnMetadata $column);

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function unserialize($data, ColumnMetadata $column);
}
