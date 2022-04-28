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

use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnProperty;

class ConditionalOnPropertyConfiguration
{
    #[ConditionalOnProperty('foo')]
    #[Bean]
    public function foo(): Foo
    {
        return new Foo('foo');
    }

    #[ConditionalOnProperty('bar')]
    #[Bean]
    public function bar()
    {
        return new Foo('bar');
    }

    #[ConditionalOnProperty('foo', 1)]
    #[Bean('foo1')]
    public function foo1(): Foo
    {
        return new Foo('foo1');
    }

    #[ConditionalOnProperty('foo', 2)]
    #[Bean('foo2')]
    public function foo2(): Foo
    {
        return new Foo('foo2');
    }
}
