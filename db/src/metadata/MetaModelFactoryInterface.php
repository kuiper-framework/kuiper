<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\CrudRepositoryInterface;

interface MetaModelFactoryInterface
{
    /**
     * Creates the table metadata.
     *
     * @param CrudRepositoryInterface $repository
     */
    public function create($repository): MetaModelInterface;
}
