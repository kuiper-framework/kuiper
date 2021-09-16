<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\reflection\filter;

use kuiper\reflection\ReflectionType;

class ArrayTypeFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testValidate()
    {
        $filter = new ArrayTypeFilter(ReflectionType::forName('int[]'));
        $this->assertTrue($filter->isValid([1]));
        $this->assertFalse($filter->isValid(['a']));
    }
}
