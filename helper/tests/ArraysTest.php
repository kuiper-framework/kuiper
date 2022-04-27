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

namespace kuiper\helper;

use kuiper\helper\fixtures\User;

/**
 * TestCase for Arrays.
 */
class ArraysTest extends TestCase
{
    public function testPull(): void
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals(['john', 'jim'], Arrays::pull($arr, 'name'));

        $objs = array_map(static function ($a) {
            return (object) $a;
        }, $arr);
        $this->assertEquals(['john', 'jim'], Arrays::pullField($objs, 'name'));

        $users = array_map(static function ($a) {
            return new User($a['name']);
        }, $arr);
        $this->assertEquals(['john', 'jim'], Arrays::pull($users, 'name'));

        $arr = ['john' => [1, 2], 'jim' => [3, 4]];
        $this->assertEquals([2, 4], Arrays::pull($arr, 1));
    }

    public function testAssoc(): void
    {
        $arr = [['name' => 'john'], ['name' => 'jim']];
        $this->assertEquals([
            'john' => ['name' => 'john'],
            'jim' => ['name' => 'jim'],
        ], Arrays::assoc($arr, 'name'));

        $objs = array_map(static function ($a) {
            return (object) $a;
        }, $arr);
        $this->assertEquals([
            'john' => $objs[0],
            'jim' => $objs[1],
        ], Arrays::assocByField($objs, 'name'));

        $users = array_map(function ($a) {
            return new User($a['name']);
        }, $arr);
        $this->assertEquals([
            'john' => $users[0],
            'jim' => $users[1],
        ], Arrays::assoc($users, 'name'));
    }

    public function testExclude(): void
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(['bar' => 2], Arrays::exclude($arr, ['foo']));
    }

    public function testRename(): void
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(['baz' => 1, 'bar' => 2], Arrays::rename($arr, ['foo' => 'baz']));
    }

    public function testSelectArray(): void
    {
        $arr = ['foo' => 1, 'bar' => 2];
        $this->assertEquals(['foo' => 1], Arrays::select($arr, ['foo']));
    }

    public function testSelectObject(): void
    {
        $arr = (object) ['foo' => 1, 'bar' => 2];
        $this->assertEquals(['foo' => 1], Arrays::selectField($arr, ['foo']));
    }

    public function testSelectGetter(): void
    {
        $user = new User('john');
        $this->assertEquals(['name' => 'john'], Arrays::select($user, ['name']));
    }

    public function testFilter(): void
    {
        $arr = ['foo' => 0, 'bar' => '', 'baz' => 1, 'bzz' => null];
        $this->assertEquals([
             'foo' => 0, 'bar' => '', 'baz' => 1,
        ], Arrays::filter($arr));
    }

    public function testAssignPublic(): void
    {
        $user = new User('john');
        $attrs = ['age' => 10, 'name' => 'mary', 'gender' => 'male', 'lastName' => 'He'];
        Arrays::assign($user, $attrs);
        $this->assertEquals('mary', $user->name);
        $this->assertEquals(10, $user->getAge());
        // $this->assertAttributeSame(null, 'gender', $user);
        // $this->assertTrue(!isset($user->lastName));
        // print_r($user);
    }

    public function testAssignPrivate(): void
    {
        $user = new User('john');
        $attrs = ['age' => 10, 'name' => 'mary', 'gender' => 'male', 'lastName' => 'He'];
        Arrays::assign($user, $attrs, false);
        $this->assertEquals('mary', $user->name);
        $this->assertEquals(10, $user->getAge());
        // $this->assertAttributeSame('male', 'gender', $user);
        // $this->assertTrue(!isset($user->lastName));
    }

    public function testToArray(): void
    {
        $user = new User('john');
        $user->setFemale(false);
        $this->assertEquals(Arrays::toArray($user), ['name' => 'john', 'age' => null, 'female' => false]);
        $this->assertEquals(Arrays::toArray($user, false), ['name' => 'john']);
    }

    public function testRecursiveToArray(): void
    {
        $user = new User('john');
        $company = new \stdClass();
        $company->employees = [$user];

        $this->assertEquals([
            'employees' => [
                [
                    'name' => 'john',
                    'age' => null,
                    'female' => null,
                ],
            ],
        ], $ret = Arrays::toArray($company, true, false, true));
    }

    public function testMapKeys(): void
    {
        $arr = Arrays::mapKeys(['fooId' => 1], [Text::class, 'snakeCase']);
        $this->assertEquals(['foo_id' => 1], $arr);

        $ret = Arrays::mapKeys($arr, function ($key) {
            return 'prefixed_'.$key;
        });
        $this->assertEquals(['foo_id' => 1], $arr);
        $this->assertEquals(['prefixed_foo_id' => 1], $ret);
    }

    public function testFlatten(): void
    {
        $this->assertEquals([1, 2, 3], Arrays::flatten([[1], [2, 3]]));

        $this->assertEquals([1, 2, 3], Arrays::flatten([
            'a' => ['a' => 1],
            'b' => ['a' => 2, 'b' => 3],
        ]));
    }

    public function testFlatten2DimArray(): void
    {
        $this->assertEquals([1, 2, 3], Arrays::flatten([[[1], [2]], [[3]]], 2));

        $this->assertEquals([1, 2, 3], Arrays::flatten([
            'a' => ['a' => [
                'a' => 1,
            ]],
            'b' => ['a' => ['b' => 2], 'b' => ['c' => 3]],
        ], 2));
    }

    public function testFlattenKeepKey(): void
    {
        $this->assertEquals(['a' => 2, 'b' => 3], Arrays::flatten([
            'a' => ['a' => 1],
            'b' => ['a' => 2, 'b' => 3],
        ], 1, true));

        $this->assertEquals(['a' => 2, 'c' => 3], Arrays::flatten([
            'a' => ['a' => [
                'a' => 1,
            ]],
            'b' => ['a' => ['a' => 2], 'b' => ['c' => 3]],
        ], 2, true));
    }

    public function testGroupWithNullKey(): void
    {
        $groups = Arrays::groupBy([['a' => null], ['a' => 'b']], 'a');
        // var_export($groups);
        $this->assertCount(2, $groups);
    }
}
