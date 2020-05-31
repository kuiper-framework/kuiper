<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\exception\MetaModelException;

interface MetaModelFactoryInterface
{
    /**
     * Creates the table metadata.
     *
     * @throws MetaModelException if column not valid
     */
    public function create(string $repositoryClass): MetaModelInterface;
}
