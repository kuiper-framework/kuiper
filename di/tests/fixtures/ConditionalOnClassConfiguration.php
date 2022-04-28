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
use kuiper\di\attribute\ConditionalOnClass;

class ConditionalOnClassConfiguration
{
    #[ConditionalOnClass(Foo::class)]
    #[Bean]
    public function foo(): Foo
    {
        return new Foo('foo');
    }

    #[ConditionalOnClass(\Bar::class)]
    #[Bean]
    public function bar()
    {
        return new Foo('bar');
    }
}
