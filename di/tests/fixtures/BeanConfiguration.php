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

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;

class BeanConfiguration
{
    /**
     * @Bean()
     */
    public function bar(): Bar
    {
        return new Bar('bar');
    }

    /**
     * @Bean(name="otherBar")
     */
    public function otherBar(): Bar
    {
        return new Bar('other');
    }

    /**
     * @Bean("foo")
     * @Inject({"bar"="otherBar"})
     */
    public function foo(Bar $bar): Bar
    {
        return $bar;
    }
}
