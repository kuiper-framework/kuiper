<?php

declare(strict_types=1);

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\TestCase;

class StatementTest extends TestCase
{
    /**
     * @var QueryBuilderInterface
     */
    private $connection;

    public function setUp(): void
    {
        $connection = new QueryBuilder(
            new SingleConnectionPool(new Connection('', '', '')),
            new QueryFactory('mysql'),
            null
        );
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
            ->orWhere(['name' => 'b', 'age' => 2]);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    (name=:_1_ AND age=:_2_)
    OR (name=:_3_ AND age=:_4_)',
            $stmt->getStatement());
    }

    public function testUpdateCaseWhen(): void
    {
        $stmt = $this->connection->update('article')
            ->set('value', 'case id '
                .'when ? then ? '
                .'when ? then ? end')
            ->bindValues([1, 3, 2, 6])
            ->in('id', [1, 2]);
        $this->assertEquals('UPDATE `article`
SET
    `value` = case id when ? then ? when ? then ? end
WHERE
    id IN (:_5_,:_6_)',
            $stmt->getStatement());
    }

    public function testUnionQuery(): void
    {
        $stmt = $this->connection->from('article1')
            ->select('author', 'count(1) count')
            ->groupBy(['author'])
            ->unionAll()
            ->from('article2')
            ->select('author', 'count(1) count')
            ->groupBy(['author']);
        $stmt = $this->connection->from('article')
            ->resetTables()
            ->fromRaw('('.$stmt->getStatement().') as t')
            ->select('author', 'sum(count) as count')
            ->groupBy(['author']);
        $this->assertEquals('SELECT
    author,
    sum(count) AS `count`
FROM
    (SELECT
    author,
    count(1) AS `count`
FROM
    `article1`
GROUP BY
    author
UNION ALL
SELECT
    author,
    count(1) AS `count`
FROM
    `article2`
GROUP BY
    author) as t
GROUP BY
    author', $stmt->getStatement());
    }

    public function testUseIndex(): void
    {
        $stmt = $this->connection->from('article')
            ->select('author')
            ->where(['category' => 'fiction'])
            ->in('author', ['john'])
            ->resetTables()
            ->fromRaw('article use index(uk_author)');
        $this->assertEquals('SELECT
    author
FROM
    article use index(uk_author)
WHERE
    (category=:_1_)
    AND author IN (:_2_)', $stmt->getStatement());
    }
}
