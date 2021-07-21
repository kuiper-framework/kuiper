<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

interface HelloService
{
    public function hello(string $name): string;
}
