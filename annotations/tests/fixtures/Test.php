<?php

declare(strict_types=1);

namespace kuiper\annotations\fixtures;

/**
 * @Foo(
 *     @Value(property="foo", value="{application.foo}/foo")
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
