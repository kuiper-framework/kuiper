<?php

declare(strict_types=1);

namespace kuiper\db;

abstract class AbstractCrudRepository implements CrudRepositoryInterface
{
    public function save($entity)
    {
        // TODO: Implement save() method.
    }

    public function saveAll(array $entities): array
    {
        // TODO: Implement saveAll() method.
    }

    public function findById($id)
    {
        // TODO: Implement findById() method.
    }

    public function existsById($id): bool
    {
        // TODO: Implement existsById() method.
    }

    public function findAllById(array $ids): array
    {
        // TODO: Implement findAllById() method.
    }

    public function deleteById($id): void
    {
        // TODO: Implement deleteById() method.
    }

    public function delete($entity): void
    {
        // TODO: Implement delete() method.
    }

    public function deleteAllById(array $ids): void
    {
        // TODO: Implement deleteAllById() method.
    }

    public function deleteAll(array $entities): void
    {
        // TODO: Implement deleteAll() method.
    }
}
