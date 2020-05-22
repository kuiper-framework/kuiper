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
    public function save($entity);

    /**
     * Saves all entities.
     */
    public function saveAll(array $entities): array;

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
     * Returns all instances of the type with the given IDs.
     */
    public function findAllById(array $ids): array;

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
}
