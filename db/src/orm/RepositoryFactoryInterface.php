<?php

declare(strict_types=1);

namespace kuiper\db\orm;

interface RepositoryFactoryInterface
{
    /**
     * Creates repository.
     *
     * @return RepositoryInterface
     */
    public function create(string $modelClass);
}
