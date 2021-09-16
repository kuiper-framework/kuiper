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

use Dotenv\Dotenv;
use kuiper\db\event\StatementQueriedEvent;
use kuiper\db\fixtures\Department;
use kuiper\db\fixtures\DepartmentRepository;
use kuiper\db\fixtures\Door;
use kuiper\db\fixtures\DoorId;
use kuiper\db\fixtures\DoorRepository;
use kuiper\db\fixtures\Item;
use kuiper\db\fixtures\ItemRepository;
use kuiper\db\metadata\MetaModelFactory;
use kuiper\db\metadata\NamingStrategy;
use function kuiper\helper\env;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RepositoryTest extends AbstractRepositoryTestCase
{
    public static function setupBeforeClass(): void
    {
        if (file_exists(__DIR__.'/.env')) {
            Dotenv::createMutable(__DIR__)->load();
        }
    }

    public function createConnection(EventDispatcherInterface $eventDispatcher): Connection
    {
        $config = $this->getConfig();
        $conn = new Connection($config[0], $config[1], $config[2]);
        $conn->setEventDispatcher($eventDispatcher);

        return $conn;
    }

    public function getConfig()
    {
        $config = [
            sprintf('mysql:dbname=%s;host=%s;port=%d;charset=%s',
                env('DB_NAME', 'test'),
                env('DB_HOST', 'localhost'),
                env('DB_PORT') ?: 3306,
                env('DB_CHARSET', 'utf8mb4')),
            env('DB_USER', 'root'),
            env('DB_PASS', ''),
        ];

        return $config;
    }

    public function createRepository($repositoryClass): AbstractCrudRepository
    {
        $eventDispatcher = new EventDispatcher();

        $eventDispatcher->addListener(StatementQueriedEvent::class, function (StatementQueriedEvent $event) {
            error_log($event->getStatement()->getStatement());
        });

        return new $repositoryClass(
            new QueryBuilder(new SingleConnectionPool($this->createConnection($eventDispatcher)), null, $eventDispatcher),
            new MetaModelFactory($this->createAttributeRegistry(), new NamingStrategy('test_'), null, null),
            new DateTimeFactory(),
            $eventDispatcher);
    }

    public function testSave()
    {
        $repository = $this->createRepository(DepartmentRepository::class);

        $department = new Department();
        $department->setName('it');
        $result = $repository->save($department);
        // var_export($result);
        $this->assertNotEmpty($result->getId());
    }

    public function testBatchInsert()
    {
        $repository = $this->createRepository(DepartmentRepository::class);
        $repository->deleteAllBy(Criteria::create());

        $result = $repository->batchInsert([
            self::department('it', '100'),
            self::department('bi'),
        ]);
        $this->assertCount(2, $result);

        // var_export($result);
        $result[0]->setDepartNo('99');
        $result[1]->setDepartNo('98');

        $result = $repository->batchUpdate($result);
        $this->assertCount(2, $result);
    }

    public function testSaveDoor()
    {
        /** @var DoorRepository $repository */
        $repository = $this->createRepository(DoorRepository::class);

        $door = new Door(new DoorId('a01'));
        $door->setName('it');
        $result = $repository->save($door);
        // var_export($result);
        $this->assertNotNull($result);
    }

    public function testFindById()
    {
        /** @var DoorRepository $repository */
        $repository = $this->createRepository(DoorRepository::class);
        $repository->deleteAllBy(Criteria::create());

        $door = new Door(new DoorId('a01'));
        $door->setName('it');
        $repository->insert($door);

        $door = $repository->findById(new DoorId('a01'));
        $this->assertNotNull($door);
        // var_export($door);
    }

    public function testFindAllByNaturalId()
    {
        $repository = $this->createRepository(ItemRepository::class);
        $factory = function (string $itemNo) {
            $item = new Item();
            $item->setItemNo($itemNo);

            return $item;
        };
        $examples[] = $factory('01');
        $examples[] = $factory('02');
        $ret = $repository->findAllByNaturalId($examples);
        // var_export($ret);
        $this->assertEmpty($ret);
    }

    public static function department(string $name, ?string $departNo = null): Department
    {
        $department = new Department();
        $department->setName($name);
        if (isset($departNo)) {
            $department->setDepartNo($departNo);
        }

        return $department;
    }
}
