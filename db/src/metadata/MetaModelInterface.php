<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\annotation\Id;
use kuiper\db\annotation\NaturalId;
use kuiper\db\Criteria;

interface MetaModelInterface extends EntityMapperInterface
{
    /**
     * Gets the table name.
     */
    public function getTable(): string;

    /**
     * Gets the class.
     */
    public function getEntityClass(): \ReflectionClass;

    /**
     * Gets the database column names.
     *
     * @return string[]
     */
    public function getColumnNames(): array;

    /**
     * Gets the column objects.
     *
     * @return ColumnInterface[]
     */
    public function getColumns(): array;

    /**
     * Gets the creation timestamp column name.
     */
    public function getCreationTimestamp(): ?string;

    /**
     * Gets the update timestamp column name.
     */
    public function getUpdateTimestamp(): ?string;

    /**
     * Gets the auto increment column name.
     */
    public function getAutoIncrement(): ?string;

    /**
     * Gets the database column values that annotated with {@see Id} or {@see NaturalId}.
     *
     * @param object $entity
     */
    public function getIdValues($entity): ?array;

    /**
     * Gets the database column values that annotated with {@see NaturalId}.
     *
     * @param object $entity
     */
    public function getNaturalIdValues($entity): ?array;

    /**
     * Gets the property value annotated with {@see Id}.
     *
     * @param object $entity
     *
     * @return mixed
     */
    public function getId($entity);

    /**
     * Gets the property.
     *
     * @return MetaModelProperty
     */
    public function getProperty(string $propertyPath): ?MetaModelProperty;

    /**
     * Filters the criteria.
     */
    public function filterCriteria(Criteria $criteria): Criteria;
}
