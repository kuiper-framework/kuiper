<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
