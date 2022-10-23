<?php

declare(strict_types=1);

namespace foo;

trait MyTrait
{
}

class Foo
{
    use MyTrait;
}

namespace bar;
