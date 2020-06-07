<?php

declare(strict_types=1);

namespace kuiper\db;

use Dotenv\Dotenv;
use kuiper\db\event\StatementQueriedEvent;
use kuiper\db\fixtures\Department;
use kuiper\db\fixtures\DepartmentRepository;
use kuiper\db\fixtures\Door;
use kuiper\db\fixtures\DoorId;
use kuiper\db\fixtures\DoorRepository;
use kuiper\db\metadata\MetaModelFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RepositoryTest extends AbstractRepositoryTestCase
{
    public static function setupBeforeClass(): void
    {
        if (file_exists(__DIR__.'/.env')) {
            Dotenv::createMutable(__DIR__)->load();
        }
    }

    public function createConnection(): Connection
    {
        $config = $this->getConfig();
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(StatementQueriedEvent::class, function (StatementQueriedEvent $event) {
            error_log($event->getStatement()->getStatement());
        });
        $conn = new Connection($config[0], $config[1], $config[2]);
        $conn->setEventDispatcher($eventDispatcher);

        return $conn;
    }

    public function getConfig()
    {
        return [
            sprintf('mysql:dbname=%s;host=%s;port=%d;charset=%s',
                getenv('DB_NAME') ?: 'test',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_PORT') ?: 3306,
            getenv('DB_CHARSET') ?: 'utf8mb4'),
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: '',
        ];
    }

    public function createRepository($repositoryClass): AbstractCrudRepository
    {
        return new $repositoryClass(
            new QueryBuilder(new ConnectionPool($this->createConnection()), null, null),
            new MetaModelFactory($this->createAttributeRegistry(), null, null, null),
            new DateTimeFactory(),
            new EventDispatcher());
    }

    public function testSave()
    {
        $repository = $this->createRepository(DepartmentRepository::class);

        $department = new Department();
        $department->setName('it');
        $result = $repository->save($department);
        var_export($result);
    }

    public function testSaveDoor()
    {
        /** @var DoorRepository $repository */
        $repository = $this->createRepository(DoorRepository::class);

        $door = new Door(new DoorId('a01'));
        $door->setName('it');
        $result = $repository->save($door);
        var_export($result);
    }

    public function testFindById()
    {
        /** @var DoorRepository $repository */
        $repository = $this->createRepository(DoorRepository::class);
        $door = $repository->findById(new DoorId('a01'));
        var_export($door);
    }
}
