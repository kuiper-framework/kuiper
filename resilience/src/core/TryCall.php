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

class TryCall
{
    /**
     * @var mixed
     */
    private $result;
    /**
     * @var \Exception|null
     */
    private $exception;

    /**
     * @param mixed ...$args
     */
    public static function call(callable $call, ...$args): TryCall
    {
        $result = new self();
        try {
            $result->result = call_user_func_array($call, $args);
        } catch (\Exception $e) {
            $result->exception = $e;
        }

        return $result;
    }

    public function isFailure(): bool
    {
        return isset($this->exception);
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }
}
