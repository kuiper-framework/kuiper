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
            sprintf('mysql:dbname=%s;host=%s;port=%d',
                getenv('DB_NAME') ?: 'test',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_PORT') ?: 3306),
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: '',
        ];
    }

    public function createRepository($repositoryClass)
    {
        return new $repositoryClass(
            $this->createConnection(),
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
    }

    public function testSaveDor()
    {
        $repository = $this->createRepository(DoorRepository::class);

        $door = new Door(new DoorId('a01'));
        $door->setName('it');
        $result = $repository->save($door);
    }
}
