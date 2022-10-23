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

namespace kuiper\db;

use kuiper\db\annotation\NaturalId;
use kuiper\db\metadata\MetaModelInterface;

interface CrudRepositoryInterface
{
    /**
     * Saves the entity.
     *
     * @param object $entity
     *
     * @return object the entity
     */
    public function insert(object $entity): object;

    /**
     * Batch insert entities.
     */
    public function batchInsert(array $entities): array;

    /**
     * Updates the entity.
     *
     * @param object $entity
     *
     * @return object the entity
     */
    public function update(object $entity): object;

    /**
     * Batch update entities.
     */
    public function batchUpdate(array $entities): array;

    /**
     * Saves the entity.
     *
     * @param object $entity
     *
     * @return object the entity
     */
    public function save(object $entity): object;

    /**
     * Batch save entities.
     */
    public function batchSave(array $entities): array;

    /**
     * Update by criteria.
     *
     * @param array|Criteria|callable $criteria
     * @param array|callable          $update
     */
    public function updateBy(mixed $criteria, array|callable $update): void;

    /**
     * Finds the entity by id.
     *
     * @param mixed $id
     *
     * @return object|null the entity
     */
    public function findById(mixed $id): ?object;

    /**
     * Returns whether an entity with the given id exists.
     *
     * @param mixed $id
     */
    public function existsById(mixed $id): bool;

    /**
     * Finds the entity.
     *
     * @param array|callable|Criteria $criteria
     *
     * @return object|null the entity
     */
    public function findFirstBy(mixed $criteria): ?object;

    /**
     * Find the entity by fields annotated with @{@see NaturalId}.
     *
     * @param object $example
     *
     * @return object|null the entity
     */
    public function findByNaturalId(object $example): ?object;

    /**
     * Find all entities by fields annotated with @{@see NaturalId}.
     *
     * @return object[] the entity
     */
    public function findAllByNaturalId(array $examples): array;

    /**
     * Returns all instances of the type with the given IDs.
     */
    public function findAllById(array $ids): array;

    /**
     * Returns all entities match the criteria.
     *
     * @param array|callable|Criteria $criteria
     */
    public function findAllBy(mixed $criteria): array;

    /**
     * Query with the given criteria.
     *
     * @param array|callable|Criteria $criteria
     */
    public function query(mixed $criteria): array;

    /**
     * Returns row count match the criteria.
     *
     * @param array|callable|Criteria $criteria
     */
    public function count(mixed $criteria): int;

    /**
     * Finds the entity by id.
     *
     * @param mixed $id
     */
    public function deleteById(mixed $id): void;

    /**
     * Deletes the entity.
     *
     * @param object $entity
     */
    public function delete(object $entity): void;

    /**
     * Delete first entity matches criteria.
     *
     * @param Criteria|callable $criteria
     */
    public function deleteFirstBy(mixed $criteria): void;

    /**
     * Delete all entity by id.
     */
    public function deleteAllById(array $ids): void;

    /**
     * Deletes all entities.
     *
     * @param object[] $entities
     *
     * @return void
     */
    public function deleteAll(array $entities): void;

    /**
     * Delete all matched.
     *
     * @param Criteria|callable $criteria
     */
    public function deleteAllBy(mixed $criteria): void;

    public function getQueryBuilder(): QueryBuilderInterface;

    public function getMetaModel(): MetaModelInterface;
}
