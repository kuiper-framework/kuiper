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

namespace kuiper\db\metadata;

use kuiper\db\annotation\Id;
use kuiper\db\annotation\NaturalId;
use kuiper\db\Criteria;
use ReflectionClass;

interface MetaModelInterface extends EntityMapperInterface
{
    /**
     * Gets the table name.
     */
    public function getTable(): string;

    /**
     * Gets the class.
     */
    public function getEntityClass(): ReflectionClass;

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
     * Gets the unique key index name.
     */
    public function getNaturalIdIndex(): ?string;

    /**
     * Gets the database column values that annotated with {@see Id} or {@see NaturalId}.
     */
    public function getIdValues(object $entity): ?array;

    /**
     * Gets the database column values that annotated with {@see NaturalId}.
     */
    public function getNaturalIdValues(object $entity): ?array;

    /**
     * Gets the unique key by join natural id values.
     */
    public function getUniqueKey(object $entity, string $joiner = "\x01", bool $ignoreCase = true): string;

    /**
     * Gets the property value annotated with {@see Id}.
     *
     * @return mixed
     */
    public function getId(object $entity);

    /**
     * Gets the property.
     */
    public function getProperty(string $propertyPath): ?MetaModelProperty;

    /**
     * Filters the criteria.
     */
    public function filterCriteria(Criteria $criteria): Criteria;
}
