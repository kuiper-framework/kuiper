<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

interface NamingStrategyInterface
{
    /**
     * Converts table name to physical table name.
     */
    public function toTableName(NamingContext $context): string;

    /**
     * Converts column name to physical column name.
     */
    public function toColumnName(NamingContext $context): string;
}
