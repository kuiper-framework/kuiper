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

class PropertiesTest extends TestCase
{
    public function createDotArray()
    {
        return Properties::create([
            'int' => 1,
            'array' => [1, 2, [3, 4]],
            'map' => [
                'k1' => 'v1',
                'k2' => 'v2',
            ],
            'map2' => [
                'k1' => ['v1'],
            ],
            'array2' => [
                ['k1' => 'v1'],
            ],
        ]);
    }

    public function testIterator()
    {
        $array = $this->createDotArray();
        $this->assertEquals([
            'int' => 1,
            'array' => [1, 2, [3, 4]],
            'map' => [
                'k1' => 'v1',
                'k2' => 'v2',
            ],
            'map2' => [
                'k1' => ['v1'],
            ],
            'array2' => [
                ['k1' => 'v1'],
            ],
        ], $array->toArray());
    }

    public function testArrayAccess()
    {
        $array = $this->createDotArray();
        // echo $array->get("map.k1");
        // return;
        foreach ([
                     'int' => 1,
                     'array[0]' => 1,
                     'array[1]' => 2,
                     'array[2][0]' => 3,
                     'array[2][1]' => 4,
                     'map.k1' => 'v1',
                     'map.k2' => 'v2',
                     'map2.k1[0]' => 'v1',
                     'array2[0].k1' => 'v1',
                 ] as $key => $val) {
            $this->assertSame($val, $array->get($key), $key.' not equal');
        }
        $this->assertEquals([
            'k1' => 'v1',
            'k2' => 'v2',
        ], $array['map']->toArray());
    }

    public function testName()
    {
        $array = $this->createDotArray();
        $this->assertEquals('v1', $array->get('array2[0].k1'));
    }

    public function testWhenMergeNotArrayKeyThenReplaceOriginal()
    {
        $p = Properties::create(['app' => ['foo' => '']]);
        $p->merge(['app' => ['foo' => ['one' => 1]], 'one' => 1]);
        $this->assertEquals($p->toArray(), ['app' => ['foo' => ['one' => 1]], 'one' => 1]);
    }

    public function testWhenMergeArrayKeyThenReplaceOriginal()
    {
        $p = Properties::create(['app' => ['foo' => '']]);
        $p->merge(['app' => ['foo' => 'one']]);
        $this->assertEquals($p->toArray(), ['app' => ['foo' => 'one']]);
    }

    public function testWhenMergeIndexBasedArrayAndNotArrayThenReplace()
    {
        $p = Properties::create(['app' => ['foo' => '']]);
        $p->merge(['app' => ['foo' => ['one']]]);
        $this->assertEquals($p->toArray(), ['app' => ['foo' => ['one']]]);
    }

    public function testWhenMergeIndexBasedArrayAndExistThenAppend()
    {
        $p = Properties::create(['app' => ['foo' => ['one']]]);
        $p->merge(['app' => ['foo' => ['two']]]);
        $this->assertEquals($p->toArray(), ['app' => ['foo' => ['one', 'two']]]);
    }

    public function testWhenMergeIndexBasedArrayAndNoAppendThenReplaceExist()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $p->merge(['app' => ['foo' => ['three']]], false);
        $this->assertEquals($p->toArray(), ['app' => ['foo' => ['three']]]);
    }

    public function testWhenGetKeyIsArrayThenReturnArray()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $this->assertEquals($p->get('app'), ['foo' => ['one', 'two']]);
    }

    public function testMergeIfNotExists()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $p->mergeIfNotExists([
            'app' => [
                'foo' => 'foo_value',
                'bar' => 'bar_value',
            ], ]);
        $this->assertEquals($p->get('app'), ['foo' => ['one', 'two'], 'bar' => 'bar_value']);
    }

    public function testMergeIfNotExistsNotExistKey()
    {
        $p = Properties::create();
        $p->mergeIfNotExists([
            'application' => [
                'commands' => ['a' => 'c'],
            ],
        ]);
    }

    public function testSetExistKey()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $p->set('app.foo', 'foo_value');
        $this->assertEquals($p->get('app'), ['foo' => 'foo_value']);
    }

    public function testSetNotExistKey()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $p->set('app.bar', 'bar_value');
        $this->assertEquals($p->get('app'), ['foo' => ['one', 'two'], 'bar' => 'bar_value']);
    }

    public function testSetNotArrayKey()
    {
        $p = Properties::create(['app' => ['foo' => ['one', 'two']]]);
        $p->set('app.foo[0].bar', 'bar_value');
        $this->assertEquals($p->get('app'), ['foo' => [['bar' => 'bar_value'], 'two']]);
    }

    public function testMergeIndexBasedArray()
    {
        $properties = Properties::create([
            'middleware' => [
                'a',
            ],
        ]);
        $properties->merge([
            'middleware' => [
                'b',
            ],
        ]);
        $this->assertEquals(['a', 'b'], $properties->get('middleware'));
    }

    public function testMergeAssocArray()
    {
        $properties = Properties::create([
            'middleware' => [
                'a',
            ],
        ]);
        $properties->merge([
            'middleware' => [
                -1 => 'c',
                100 => 'b',
            ],
        ]);
        $middleware = $properties->get('middleware');
        // var_export($middleware);
        $this->assertEquals(['a', 'c', 'b'], array_values($middleware));
        ksort($middleware);
        $this->assertEquals(['c', 'a', 'b'], array_values($middleware));
    }

    public function testMergeMixedArray()
    {
        $properties = Properties::create([
            'middleware' => [
                'a',
            ],
        ]);
        $properties->merge([
            'middleware' => [
                'd',
                -1 => 'c',
                100 => 'b',
                'e',
            ],
        ]);
        $middleware = $properties->get('middleware');
        // var_export($middleware);
        $this->assertEquals([
            0 => 'd',
            -1 => 'c',
            100 => 'b',
            101 => 'e',
        ], $middleware);
        $this->assertEquals(['d', 'c', 'b', 'e'], array_values($middleware));
        ksort($middleware);
        $this->assertEquals(['c', 'd', 'b', 'e'], array_values($middleware));
    }

    public function testMergeToAssocArray()
    {
        $properties = Properties::create([
            'middleware' => [
                1000 => 'a',
            ],
        ]);
        $properties->merge([
            'middleware' => [
                1 => 'b',
            ],
        ]);
        $middleware = $properties->get('middleware');
        var_export($middleware);
        $this->assertEquals([
            0 => 'd',
            -1 => 'c',
            100 => 'b',
            101 => 'e',
        ], $middleware);
        $this->assertEquals(['d', 'c', 'b', 'e'], array_values($middleware));
        ksort($middleware);
        $this->assertEquals(['c', 'd', 'b', 'e'], array_values($middleware));
    }

    public function testReplacePlaceholder()
    {
        $properties = Properties::create([
            'base_path' => '/path',
            'view_path' => '{base_path}/views',
            'tmpl_path' => '{view_path}/a.html',
        ]);
        $properties->replacePlaceholder();
        $this->assertEquals([
            'base_path' => '/path',
            'view_path' => '/path/views',
            'tmpl_path' => '/path/views/a.html',
        ], $properties->toArray());
    }

    public function testReplacePlaceholderWithPredicate()
    {
        $properties = Properties::create([
            'application' => [
                'base_path' => '{ENV.APP_PATH}',
            ],
            'ENV' => [
                'BASE_MODULE' => 'function () { eval }',
                'APP_PATH' => '/path',
            ],
        ]);
        // $properties->replacePlaceholder();
        $properties->replacePlaceholder(function (string $key) {
            return !Text::startsWith($key, 'ENV.');
        });
        $this->assertEquals([
            'application' => [
                    'base_path' => '/path',
                ],
            'ENV' => [
                    'BASE_MODULE' => 'function () { eval }',
                    'APP_PATH' => '/path',
                ],
        ], $properties->toArray());
    }
}
