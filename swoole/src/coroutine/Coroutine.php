<?php

declare(strict_types=1);

namespace kuiper\swoole\coroutine;

use Swoole\Coroutine as SwooleCoroutine;
use Swoole\Runtime;

final class Coroutine
{
    private const NOT_COROUTINE_ID = 0;

    private static $HOOK_FLAGS = SWOOLE_HOOK_ALL;

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
        error_log('get context: coroutine enabled '.self::isEnabled().' coid='.$coroutineId);

        if (self::isEnabled()) {
            return isset($coroutineId) ? SwooleCoroutine::getContext($coroutineId) : SwooleCoroutine::getContext();
        }

        if (isset(self::$CONTEXT)) {
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
}
