<?php

declare(strict_types=1);

namespace kuiper\db;

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
     * Saves the entity.
     *
     * @param object $entity
     *
     * @return object the entity
     */
    public function update($entity);

    /**
     * Saves the entity.
     *
     * @param object $entity
     *
     * @return object the entity
     */
    public function save($entity);

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

    public function deleteAllById(array $ids): void;

    public function deleteAll(array $entities): void;

    public function deleteAllBy($criteria): void;
}
