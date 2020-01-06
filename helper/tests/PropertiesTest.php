<?php

namespace kuiper\helper;

class PropertiesTest extends TestCase
{
    public function createDotArray()
    {
        return Properties::fromArray([
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
}
