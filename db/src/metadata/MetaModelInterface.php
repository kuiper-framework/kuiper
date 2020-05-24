<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

interface MetaModelInterface extends EntityMapperInterface
{
    /**
     * Gets the table name.
     */
    public function getTable(): string;

    /**
     * Gets the database column names.
     *
     * @return string[]
     */
    public function getColumnNames(): array;

    public function getCreationTimestamp(): ?string;

    public function getUpdateTimestamp(): ?string;

    public function getAutoIncrement(): ?string;

    public function getUniqueKey($entity): array;

    public function getId($entity);
}
