<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;

class TwoConditionConfiguration
{
    /**
     * @ConditionalOnProperty("foo")
     * @Bean()
     */
    public function foo(): Foo
    {
        return new Foo('foo');
    }

    /**
     * @ConditionalOnProperty("bar")
     * @Bean()
     */
    public function bar(): Foo
    {
        return new Foo('bar');
    }
}
