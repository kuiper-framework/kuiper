<?php

declare(strict_types=1);

namespace kuiper\db;

interface QueryBuilderInterface
{
    /**
     * Create select statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newSelect()
     */
    public function from(string $table): StatementInterface;

    /**
     * Create delete statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newDelete()
     */
    public function delete(string $table): StatementInterface;

    /**
     * Create update statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newUpdate()
     */
    public function update(string $table): StatementInterface;

    /**
     * Create insert statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newInsert()
     */
    public function insert(string $table): StatementInterface;
}
