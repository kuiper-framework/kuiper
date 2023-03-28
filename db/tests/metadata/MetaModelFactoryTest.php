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

use Carbon\Carbon;
use kuiper\db\AbstractRepositoryTestCase;
use kuiper\db\fixtures\Department;
use kuiper\db\fixtures\Door;
use kuiper\db\fixtures\DoorId;
use kuiper\db\fixtures\Gender;
use kuiper\db\fixtures\GenderEnum;
use kuiper\db\fixtures\Student;
use kuiper\db\fixtures\StudentEnum;
use kuiper\db\fixtures\StudentEnumString;
use kuiper\db\fixtures\User;
use kuiper\reflection\ReflectionDocBlockFactory;

class MetaModelFactoryTest extends AbstractRepositoryTestCase
{
    /**
     * @var MetaModelFactory
     */
    private $metaModelFactory;

    public function setUp(): void
    {
        $this->metaModelFactory = new MetaModelFactory($this->createAttributeRegistry(), new NamingStrategy(), ReflectionDocBlockFactory::getInstance());
    }

    public function testCreate()
    {
        $metaModel = $this->metaModelFactory->create(Department::class);
        self::assertEquals('department', $metaModel->getTable());
        self::assertEquals([
            'id',
            'create_time',
            'update_time',
            'name',
            'depart_no',
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
        $this->assertEquals(['id' => $department->getId()], $metaModel->getIdValues($department));
    }

    public function testEmbedId()
    {
        $metaModel = $this->metaModelFactory->create(Door::class);
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
        $this->assertEquals(['door_code' => $door->getDoorId()->getValue()], $metaModel->getIdValues($door));
    }

    public function testDateAttribute()
    {
        $metaModel = $this->metaModelFactory->create(User::class);
        $user = new User();
        $user->setDob(Carbon::parse('2020-03-01'));
        $row = $metaModel->freeze($user);
        $this->assertEquals([
            'dob' => '2020-03-01',
        ], $row);
    }

    public function testPhpEnumField()
    {
        $metaModel = $this->metaModelFactory->create(Student::class);
        $student = new Student();
        $student->setGender(Gender::FEMALE);
        $row = $metaModel->freeze($student);
        $this->assertEquals(['gender' => 'FEMALE'], $row);
    }

    public function testEnumField()
    {
        $metaModel = $this->metaModelFactory->create(StudentEnum::class);
        $student = new StudentEnum();
        $student->setGender(GenderEnum::FEMALE());
        $row = $metaModel->freeze($student);
        $this->assertEquals(['gender' => 1], $row);
    }

    public function testEnumStringField()
    {
        $metaModel = $this->metaModelFactory->create(StudentEnumString::class);
        $student = new StudentEnumString();
        $student->setGender(GenderEnum::FEMALE());
        $row = $metaModel->freeze($student);
        $this->assertEquals(['gender' => 'FEMALE'], $row);
    }
}
