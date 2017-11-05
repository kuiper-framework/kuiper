<?php

namespace kuiper\reflection\filter;

class BooleanTypeFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider valuesForValidate
     */
    public function testValidate($value, $valid)
    {
        $filter = new BooleanTypeFilter();
        $this->assertEquals($valid, $filter->validate($value));
    }

    public function valuesForValidate()
    {
        foreach (['1', 'true', 'on', 'yes', true, '0', 'false', 'off', 'no', '', false, null] as $value) {
            yield [$value, true];
        }
        foreach (['xxx', 3] as $value) {
            yield [$value, false];
        }
    }

    /**
     * @dataProvider valuesForSanitize
     *
     * @param $value
     * @param $expected
     */
    public function testSanitize($value, $expected)
    {
        $filter = new BooleanTypeFilter();
        $this->assertEquals($expected, $filter->sanitize($value));
    }

    public function valuesForSanitize()
    {
        foreach (['1', 'true', 'on', 'yes', true] as $value) {
            yield [$value, true];
        }
        foreach (['0', 'false', 'off', 'no', '', false, null] as $value) {
            yield [$value, false];
        }
        foreach (['xxx', 3] as $value) {
            yield [$value, false];
        }
    }
}
