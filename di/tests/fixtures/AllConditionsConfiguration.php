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

namespace kuiper\di\fixtures;

use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnProperty;

class AllConditionsConfiguration
{
    #[AllConditions(new ConditionalOnProperty('foo', 'foo1'), new ConditionalOnProperty('foo1'))]
    #[Bean]
    public function foo1(): Foo
    {
        return new Foo('foo1');
    }

    #[AllConditions(new ConditionalOnProperty('foo', 'foo2'), new ConditionalOnProperty('foo2'))]
    #[Bean]
    public function foo2(): Foo
    {
        return new Foo('foo2');
    }
}
