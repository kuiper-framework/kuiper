<?php

declare(strict_types=1);

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\TestCase;

class CriteriaTest extends TestCase
{
    /**
     * @var StatementInterface
     */
    private $statement;

    public function setUp(): void
    {
        $pool = new SingleConnectionPool(new Connection('', '', ''));
        $queryBuilder = new QueryBuilder($pool, new QueryFactory('mysql'), null);
        $this->statement = $queryBuilder->from('article')
            ->select('*');
    }

    public function testWhere(): void
    {
        $query = Criteria::create(['id' => 1])
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    id = :_1_', $query->getStatement());
    }

    public function testInWhere(): void
    {
        $query = Criteria::create()
            ->in('id', [2 => 1])
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    id IN (:_1_)', $query->getStatement());
        //$bindValues = $query->getBindValues();
        //var_export($bindValues);
    }

    public function testWhereMultipleFields(): void
    {
        $query = Criteria::create(['author' => 'john', 'tag' => 'a', 'category' => 'c'])
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    ((author = :_1_) AND (tag = :_2_)) AND (category = :_3_)', $query->getStatement());
        $this->assertEquals([
            '_1_' => 'john',
            '_2_' => 'a',
            '_3_' => 'c',
        ], $query->getBindValues());
    }

    public function testNot(): void
    {
        $query = Criteria::create()
            ->not(Criteria::create(['id' => 1]))
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    !(id = :_1_)', $query->getStatement());
    }

    public function testMatches(): void
    {
        $query = Criteria::create()
            ->matches([
                ['scope_id' => 'a1', 'name' => 'count', 'tags' => ''],
                ['scope_id' => 'a2', 'name' => 'count', 'tags' => ''],
                ['scope_id' => 'a1', 'name' => 'amount', 'tags' => ''],
            ], ['scope_id', 'name', 'tags'])
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    (tags = :_1_) AND (((scope_id = :_2_) AND (name IN (:_3_,:_4_))) OR ((scope_id = :_5_) AND (name = :_6_)))',
            $query->getStatement());
    }

    public function testMatchesAndOtherCriteria()
    {
        $query = Criteria::create(['sharding' => 1])
            ->matches([
                ['scope_id' => 'a1', 'name' => 'count', 'tags' => ''],
                ['scope_id' => 'a2', 'name' => 'count', 'tags' => ''],
                ['scope_id' => 'a1', 'name' => 'amount', 'tags' => ''],
            ], ['scope_id', 'name', 'tags'])
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `article`
WHERE
    (sharding = :_1_) AND ((tags = :_2_) AND (((scope_id = :_3_) AND (name IN (:_4_,:_5_))) OR ((scope_id = :_6_) AND (name = :_7_))))',
            $query->getStatement());
    }
}
