<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;

class ConditionalOnPropertyConfiguration
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
     * @Bean("bar")
     */
    public function bar()
    {
        return new Foo('bar');
    }

    /**
     * @ConditionalOnProperty(value="foo", hasValue="1")
     * @Bean("foo1")
     */
    public function foo1(): Foo
    {
        return new Foo('foo1');
    }

    /**
     * @ConditionalOnProperty(value="foo", hasValue="2")
     * @Bean("foo2")
     */
    public function foo2(): Foo
    {
        return new Foo('foo2');
    }
}
