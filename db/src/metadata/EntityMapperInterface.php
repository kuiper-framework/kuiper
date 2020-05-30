<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

interface EntityMapperInterface
{
    /**
     * Converts entity object to database column values.
     *
     * @param object $entity entity
     *
     * @return array the column values
     */
    public function freeze($entity): array;

    /**
     * Converts database column values.
     *
     * @return object $entity
     */
    public function thaw(array $columnValues);

    /**
     * Gets the database column value from entity object.
     *
     * @param object $entity     entity
     * @param string $columnName the database column name
     *
     * @return string|int|null the database column value
     */
    public function getValue($entity, string $columnName);

    /**
     * Sets the entity property value.
     *
     * @param object     $entity
     * @param string     $columnName the database column name
     * @param string|int $value      the database column name
     */
    public function setValue($entity, string $columnName, $value): void;

    /**
     * Converts id value to database column value.
     *
     * @param mixed $id
     */
    public function idToPrimaryKey($id): array;
}
