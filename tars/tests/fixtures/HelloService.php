<?php

declare(strict_types=1);

namespace kuiper\tars\fixtures;

use kuiper\tars\annotation\TarsParameter;
use kuiper\tars\annotation\TarsReturnType;
use kuiper\tars\annotation\TarsServant;

/**
 * @TarsServant("app.hello.HelloObj")
 */
interface HelloService
{
    /**
     * @TarsParameter(type="string", name="name")
     * @TarsReturnType("string")
     */
    public function hello(string $name): string;
}
