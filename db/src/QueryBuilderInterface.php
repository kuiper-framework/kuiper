<?php

declare(strict_types=1);

namespace kuiper\db;

interface QueryBuilderInterface
{
    /**
     * Create select statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newSelect()
     *
     * @param string $table
     */
    public function from($table): StatementInterface;

    /**
     * Create delete statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newDelete()
     *
     * @param string $table
     */
    public function delete($table): StatementInterface;

    /**
     * Create update statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newUpdate()
     *
     * @param string $table
     */
    public function update($table): StatementInterface;

    /**
     * Create insert statement.
     *
     * @see \Aura\SqlQuery\QueryFactory::newInsert()
     *
     * @param string $table
     */
    public function insert($table): StatementInterface;
}
