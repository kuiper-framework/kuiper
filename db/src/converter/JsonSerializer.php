<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\orm\ColumnMetadata;

class JsonSerializer implements Serializer
{
    public function serialize($value, ColumnMetadata $column)
    {
        return json_encode($value);
    }

    public function unserialize($data, ColumnMetadata $column)
    {
        return json_decode($data, true);
    }
}
