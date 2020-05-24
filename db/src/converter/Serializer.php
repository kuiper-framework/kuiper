<?php

declare(strict_types=1);

namespace kuiper\db\orm\serializer;

use kuiper\db\metadata\Column;

interface Serializer
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function serialize($value, Column $column);

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function unserialize($data, Column $column);
}
