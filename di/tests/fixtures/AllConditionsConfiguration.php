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

use kuiper\di\annotation\AllConditions;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;

class AllConditionsConfiguration
{
    /**
     * @AllConditions({
     *     @ConditionalOnProperty(value="foo", hasValue="foo1"),
     *     @ConditionalOnProperty(value="foo1")
     * })
     * @Bean
     */
    public function foo1(): Foo
    {
        return new Foo('foo1');
    }

    /**
     * @AllConditions({
     *     @ConditionalOnProperty(value="foo", hasValue="foo2"),
     *     @ConditionalOnProperty(value="foo2")
     * })
     * @Bean
     */
    public function foo2(): Foo
    {
        return new Foo('foo2');
    }
}
