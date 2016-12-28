<?php
namespace foo;

use My\Full\Classname as Another;

trait MyTrait
{
}

class Foo
{
    use MyTrait;
}

namespace bar;

use My\Full\Classname;
