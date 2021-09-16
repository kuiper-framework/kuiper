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

namespace kuiper\swoole\coroutine;

use Swoole\Coroutine as SwooleCoroutine;
use Swoole\Runtime;

final class Coroutine
{
    private const NOT_COROUTINE_ID = 0;

    /**
     * 不开启 SWOOLE_HOOK_CURL.
     *
     * @var int
     */
    private static $HOOK_FLAGS = SWOOLE_HOOK_TCP | SWOOLE_HOOK_UDP | SWOOLE_HOOK_UNIX | SWOOLE_HOOK_UDG | SWOOLE_HOOK_SSL | SWOOLE_HOOK_TLS | SWOOLE_HOOK_SLEEP | SWOOLE_HOOK_FILE | SWOOLE_HOOK_STREAM_SELECT | SWOOLE_HOOK_BLOCKING_FUNCTION;

    /**
     * @var \ArrayObject|null
     */
    private static $CONTEXT;

    public static function isEnabled(): bool
    {
        return SwooleCoroutine::getCid() > self::NOT_COROUTINE_ID;
    }

    public static function enable(): void
    {
        Runtime::enableCoroutine(true, self::$HOOK_FLAGS);
    }

    public static function disable(): void
    {
        Runtime::enableCoroutine(false);
    }

    public static function getCoroutineId(): int
    {
        if (self::isEnabled()) {
            return SwooleCoroutine::getCid();
        }

        return self::NOT_COROUTINE_ID;
    }

    public static function setHookFlags(int $flags): void
    {
        self::$HOOK_FLAGS = $flags;
    }

    public static function getContext(int $coroutineId = null): \ArrayObject
    {
        if (self::isEnabled()) {
            return isset($coroutineId) ? SwooleCoroutine::getContext($coroutineId) : SwooleCoroutine::getContext();
        }

        if (null === self::$CONTEXT) {
            self::$CONTEXT = new \ArrayObject();
        }

        return self::$CONTEXT;
    }

    public static function clearContext(): void
    {
        if (self::isEnabled()) {
            return;
        }
        self::$CONTEXT = null;
    }

    public static function defer(callable $callback): void
    {
        if (self::isEnabled()) {
            SwooleCoroutine::defer($callback);
        } else {
            register_shutdown_function($callback);
        }
    }
}
