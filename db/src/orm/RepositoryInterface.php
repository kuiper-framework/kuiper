<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\db\StatementInterface;

interface RepositoryInterface extends \kuiper\db\RepositoryInterface
{
    /**
     * Do insert or update record according to the unique constraint.
     *
     * @param object $model
     *
     * @return object
     *
     * @throws \InvalidArgumentException if no unique constraint found in the model
     */
    public function save($model);

    /**
     * Gets last executed statement.
     *
     * @return StatementInterface
     */
    public function getLastStatement();
}
