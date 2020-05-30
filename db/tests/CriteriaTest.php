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
        $connection = new Connection('', '', '');
        $connection->setQueryFactory(new QueryFactory('mysql'));
        $this->statement = $connection->from('article')
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
    id=:_1_', $query->getStatement());
    }
}
