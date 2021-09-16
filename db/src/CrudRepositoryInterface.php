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
    public function insert($entity);

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
    public function update($entity);

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
    public function save($entity);

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
    public function updateBy($criteria, $update): void;

    /**
     * Finds the entity by id.
     *
     * @param mixed $id
     *
     * @return object|null the entity
     */
    public function findById($id);

    /**
     * Returns whether an entity with the given id exists.
     *
     * @param mixed $id
     */
    public function existsById($id): bool;

    /**
     * Finds the entity.
     *
     * @param array|callable|Criteria $criteria
     *
     * @return object|null the entity
     */
    public function findFirstBy($criteria);

    /**
     * Find the entity by fields annotated with @{@see NaturalId}.
     *
     * @param object $example
     *
     * @return object|null the entity
     */
    public function findByNaturalId($example);

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
    public function findAllBy($criteria): array;

    /**
     * Query with the given criteria.
     *
     * @param array|callable|Criteria $criteria
     */
    public function query($criteria): array;

    /**
     * Returns row count match the criteria.
     *
     * @param array|callable|Criteria $criteria
     */
    public function count($criteria): int;

    /**
     * Finds the entity by id.
     *
     * @param mixed $id
     */
    public function deleteById($id): void;

    /**
     * Deletes the entity.
     *
     * @param object $entity
     */
    public function delete($entity): void;

    /**
     * Delete first entity matches criteria.
     *
     * @param Criteria|callable $criteria
     */
    public function deleteFirstBy($criteria): void;

    /**
     * Delete all entity by id.
     */
    public function deleteAllById(array $ids): void;

    public function deleteAll(array $entities): void;

    /**
     * Delete all matched.
     *
     * @param Criteria|callable $criteria
     */
    public function deleteAllBy($criteria): void;

    public function getQueryBuilder(): QueryBuilderInterface;

    public function getMetaModel(): MetaModelInterface;
}
