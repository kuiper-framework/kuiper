<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;

class ConditionalOnClassConfiguration
{
    /**
     * @ConditionalOnClass("kuiper\di\fixtures\Foo")
     * @Bean()
     */
    public function foo(): Foo
    {
        return new Foo('foo');
    }

    /**
     * @ConditionalOnClass(Bar::class)
     * @Bean("bar")
     */
    public function bar()
    {
        return new Foo('bar');
    }
}
