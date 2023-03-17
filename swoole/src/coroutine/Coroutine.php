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

use ArrayObject;
use Swoole\Coroutine as SwooleCoroutine;

final class Coroutine
{
    private const NOT_COROUTINE_ID = 0;

    /**
     * 不开启 SWOOLE_HOOK_CURL.
     *
     * @var int
     */
    private static int $HOOK_FLAGS = SWOOLE_HOOK_ALL;

    private static ?ArrayObject $CONTEXT;

    public static function isEnabled(): bool
    {
        return SwooleCoroutine::getCid() > self::NOT_COROUTINE_ID;
    }

    public static function enable(): void
    {
        SwooleCoroutine::set([
            'hook_flags' => self::$HOOK_FLAGS,
        ]);
    }

    /**
     * @deprecated
     */
    public static function disable(): void
    {
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

    public static function getContext(int $coroutineId = null): ArrayObject
    {
        if (self::isEnabled()) {
            return isset($coroutineId) ? SwooleCoroutine::getContext($coroutineId) : SwooleCoroutine::getContext();
        }

        if (!isset(self::$CONTEXT)) {
            self::$CONTEXT = new ArrayObject();
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
