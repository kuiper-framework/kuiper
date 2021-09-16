<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
