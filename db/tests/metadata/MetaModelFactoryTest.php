<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use Carbon\Carbon;
use kuiper\db\AbstractRepositoryTestCase;
use kuiper\db\fixtures\Department;
use kuiper\db\fixtures\DepartmentRepository;
use kuiper\db\fixtures\Door;
use kuiper\db\fixtures\DoorId;
use kuiper\db\fixtures\DoorRepository;

class MetaModelFactoryTest extends AbstractRepositoryTestCase
{
    /**
     * @var MetaModelFactory
     */
    private $metaModelFactory;

    public function setUp(): void
    {
        $this->metaModelFactory = new MetaModelFactory($this->createAttributeRegistry(), null, null, null);
    }

    public function testCreate()
    {
        $metaModel = $this->metaModelFactory->create(DepartmentRepository::class);
        self::assertEquals('department', $metaModel->getTable());
        self::assertEquals([
            0 => 'id',
            1 => 'create_time',
            2 => 'update_time',
            3 => 'name',
        ], $metaModel->getColumnNames());
        $this->assertEquals('update_time', $metaModel->getUpdateTimestamp());
        $this->assertEquals('create_time', $metaModel->getCreationTimestamp());
        $this->assertEquals('id', $metaModel->getAutoIncrement());

        $department = new Department();
        $department->setId(10);
        $department->setName('it');
        $time = Carbon::parse('2020-01-01 03:02:01');
        $department->setCreateTime($time);
        $department->setUpdateTime($time);
        $values = $metaModel->freeze($department);
        $row = [
            'id' => 10,
            'create_time' => '2020-01-01 03:02:01',
            'update_time' => '2020-01-01 03:02:01',
            'name' => 'it',
        ];
        self::assertEquals($row, $values);

        $model = $metaModel->thaw($row);
        self::assertEquals($department->getId(), $model->getId());

        $this->assertEquals($department->getId(), $metaModel->getId($department));
        $this->assertEquals(['id' => $department->getId()], $metaModel->getUniqueKey($department));
    }

    public function testEmbedId()
    {
        $metaModel = $this->metaModelFactory->create(DoorRepository::class);
        self::assertEquals('door', $metaModel->getTable());
        self::assertEquals([
            'door_code',
            'name',
        ], $metaModel->getColumnNames());
        $door = new Door(new DoorId('a1'));
        $door->setName('it');
        $values = $metaModel->freeze($door);
        $row = [
            'door_code' => 'a1',
            'name' => 'it',
        ];
        self::assertEquals($row, $values);

        $model = $metaModel->thaw($row);
        self::assertEquals($door->getDoorId(), $model->getDoorId());

        $this->assertEquals($door->getDoorId(), $metaModel->getId($door));
        $this->assertEquals(['door_code' => $door->getDoorId()->getValue()], $metaModel->getUniqueKey($door));
    }
}
