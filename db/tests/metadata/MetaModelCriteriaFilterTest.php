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

namespace kuiper\db\metadata;

use Aura\SqlQuery\QueryFactory;
use kuiper\db\AbstractRepositoryTestCase;
use kuiper\db\Connection;
use kuiper\db\Criteria;
use kuiper\db\fixtures\Door;
use kuiper\db\fixtures\DoorId;
use kuiper\db\QueryBuilder;
use kuiper\db\SingleConnectionPool;

class MetaModelCriteriaFilterTest extends AbstractRepositoryTestCase
{
    /**
     * @var MetaModelInterface
     */
    private $metaModel;
    /**
     * @var \kuiper\db\StatementInterface
     */
    private $statement;

    public function setUp(): void
    {
        $pool = new SingleConnectionPool(new Connection('', '', ''));
        $queryBuilder = new QueryBuilder($pool, new QueryFactory('mysql'), null);
        $metaModelFactory = new MetaModelFactory($this->createAttributeRegistry(), null, null, null);

        $this->metaModel = $metaModelFactory->create(Door::class);
        $this->statement = $queryBuilder->from('door')
            ->select('*');
    }

    public function testFilter()
    {
        $criteria = $this->metaModel->filterCriteria(Criteria::create([
            'doorId' => new DoorId('d01'),
        ]));
        $statement = $criteria
            ->buildStatement($this->statement);
        $this->assertEquals('SELECT
    *
FROM
    `door`
WHERE
    door_code = :_1_', $statement->getStatement());
        $this->assertEquals(['_1_' => 'd01'], $statement->getBindValues());
    }
}
