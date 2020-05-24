<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\metadata\Column;

class JsonSerializer implements Serializer
{
    public function serialize($value, Column $column)
    {
        return json_encode($value);
    }

    public function unserialize($data, Column $column)
    {
        return json_decode($data, true);
    }
}
