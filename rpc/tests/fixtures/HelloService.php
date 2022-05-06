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

namespace kuiper\rpc\fixtures;

use kuiper\rpc\attribute\CircuitBreaker;
use kuiper\rpc\attribute\Retry;

#[CircuitBreaker]
interface HelloService
{
    #[Retry]
    public function hello(string $name): string;
}
