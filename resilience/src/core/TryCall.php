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

namespace kuiper\resilience\core;

final class TryCall
{
    private function __construct(private readonly mixed $result, private readonly ?\Exception $exception)
    {
    }

    public static function call(callable $call, ...$args): TryCall
    {
        $result = null;
        $exception = null;
        try {
            $result = call_user_func_array($call, $args);
        } catch (\Exception $e) {
            $exception = $e;
        }

        return new self($result, $exception);
    }

    public function isFailure(): bool
    {
        return isset($this->exception);
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }
}
