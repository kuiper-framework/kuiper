<?php

declare(strict_types=1);

namespace kuiper\annotations\fixtures;

/**
 * @Foo(
 *     @Value(property="foo", value="{application.foo}/foo"),
 *     @Value(property="value", value="{application.foo}/value")
 * )
 */
class Test
{
    /**
     * @var string
     * @Foo(bar="bar", "foo")
     */
    private $foo;
}
