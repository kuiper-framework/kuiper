<?php

declare(strict_types=1);

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function setUp(): void
    {
        $connection = new Connection('', '', '');
        $connection->setQueryFactory(new QueryFactory('mysql'));
        $this->connection = $connection;
    }

    public function testWhere(): void
    {
        $query = $this->connection->from('article')
               ->select('*')
               ->where(['id' => 1]);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    (id=:_1_)', $query->getStatement());
    }

    public function testInWhere(): void
    {
        $query = $this->connection->from('article')
               ->select('*')
               ->in('id', [1, 2]);
        $this->assertEquals(
            'SELECT
    *
FROM
    `article`
WHERE
    id IN (:_1_,:_2_)',
            $query->getStatement());
        $this->assertEquals([
            '_1_' => 1,
            '_2_' => 2,
        ], $query->getBindValues());
    }

    public function testOrInWhere(): void
    {
        $query = $this->connection->from('article')
               ->select('*')
               ->where('status=?', 'ok')
               ->orIn('id', [1, 2]);
        $this->assertEquals(
            'SELECT
    *
FROM
    `article`
WHERE
    status=:_1_
    OR id IN (:_2_,:_3_)',
            $query->getStatement());
        $this->assertEquals([
            '_1_' => 'ok',
            '_2_' => 1,
            '_3_' => 2,
        ], $query->getBindValues());
    }

    public function testOrWithArray(): void
    {
        $stmt = $this->connection->from('article')
            ->select('*')
            ->orWhere(['name' => 'a', 'age' => 1])
            ->orWhere(['name' => 'b', 'age' => 2])
            ;
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    (name=:_1_ AND age=:_2_)
    OR (name=:_3_ AND age=:_4_)',
            $stmt->getStatement());
    }
}
