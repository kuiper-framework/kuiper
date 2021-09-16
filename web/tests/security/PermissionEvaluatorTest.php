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

namespace kuiper\web\security;

use kuiper\web\fixtures\User;
use PHPUnit\Framework\TestCase;

class PermissionEvaluatorTest extends TestCase
{
    /**
     * @dataProvider hasPermissionData
     */
    public function testHasPermission(array $authorities, $resource, bool $result)
    {
        $evaluator = new PermissionEvaluator(new Acl());
        $this->assertEquals($result, $evaluator->hasPermission(new User('u', $authorities), $resource));
    }

    /**
     * @dataProvider hasAnyPermissionData
     */
    public function testHasAnyPermission(array $authorities, array $resources, bool $result)
    {
        $evaluator = new PermissionEvaluator(new Acl());
        $this->assertEquals($result, $evaluator->hasAnyPermission(new User('u', $authorities), $resources));
    }

    public function hasPermissionData()
    {
        return [
            [['book:*'], 'book:view', true],
            [['blog:*'], 'book:view', false],
            [['book:view'], 'book:view', true],
            [['book:view'], 'book:edit', false],
            [['book:*'], ['book:view', 'book:edit'], true],
            [['book:view'], ['book:view', 'book:edit'], false],
            [['book:view', 'book:edit'], ['book:view', 'book:edit'], true],
        ];
    }

    public function hasAnyPermissionData()
    {
        return [
            [['book:*'], ['book:view'], true],
            [['blog:*'], ['book:view'], false],
            [['book:view'], ['book:view'], true],
            [['book:view'], ['book:edit'], false],
            [['book:*'], ['book:view', 'book:edit'], true],
            [['book:view'], ['book:view', 'book:edit'], true],
        ];
    }
}
