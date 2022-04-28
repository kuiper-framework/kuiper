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

use DI\Attribute\Inject;
use kuiper\di\attribute\Bean;

class BeanConfiguration
{
    #[Bean]
    public function bar(): Bar
    {
        return new Bar('bar');
    }

    #[Bean('otherBar')]
    public function otherBar(): Bar
    {
        return new Bar('other');
    }

    #[Bean('foo')]
    public function foo(#[Inject('otherBar')] Bar $bar): Bar
    {
        return $bar;
    }
}
