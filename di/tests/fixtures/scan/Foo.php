<?php

declare(strict_types=1);

namespace kuiper\di\fixtures\scan;

use kuiper\di\attribute\Component;
use kuiper\di\attribute\ComponentScan;

#[Component]
#[ComponentScan(basePackages: [Foo::NS])]
class Foo
{
    public const NS = 'kuiper\\di\\fixtures\\scan1';
}
