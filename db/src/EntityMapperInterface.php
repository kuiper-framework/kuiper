<?php

declare(strict_types=1);

namespace kuiper\db;

interface EntityMapperInterface
{
    public function freeze($entity): array;

    public function thaw(array $fields);

    public function getValue($entity, string $columnName);

    public function setValue($entity, string $columnName, $value);
}
