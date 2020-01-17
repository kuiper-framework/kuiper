<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperties;

class TwoConditionConfiguration
{
    /**
     * @ConditionalOnProperties("foo")
     * @Bean()
     */
    public function foo(): Foo
    {
        return new Foo('foo');
    }

    /**
     * @ConditionalOnProperties("bar")
     * @Bean()
     */
    public function bar(): Foo
    {
        return new Foo('bar');
    }
}
