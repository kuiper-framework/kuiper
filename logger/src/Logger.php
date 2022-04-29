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

namespace kuiper\logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Logger extends AbstractLogger
{
    /**
     * @var int[]
     */
    private static array $LEVELS = [
        LogLevel::EMERGENCY => 1,
        LogLevel::ALERT => 2,
        LogLevel::CRITICAL => 3,
        LogLevel::ERROR => 4,
        LogLevel::WARNING => 5,
        LogLevel::NOTICE => 6,
        LogLevel::INFO => 7,
        LogLevel::DEBUG => 8,
    ];

    /**
     * @var int
     */
    private int $logLevel;

    private static ?NullLogger $NULL_LOGGER = null;

    public function __construct(private readonly LoggerInterface $logger, string $logLevel)
    {
        if (!isset(self::$LEVELS[$logLevel])) {
            throw new \InvalidArgumentException("Unknown log level '$logLevel'");
        }
        $this->logLevel = self::$LEVELS[$logLevel];
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (self::$LEVELS[$level] <= $this->logLevel) {
            $this->logger->log($level, $message, $context);
        }
    }

    public static function getLevel(string $level): ?int
    {
        return self::$LEVELS[$level] ?? null;
    }

    public static function nullLogger(): LoggerInterface
    {
        if (!isset(self::$NULL_LOGGER)) {
            self::$NULL_LOGGER = new NullLogger();
        }
        return self::$NULL_LOGGER;
    }
}
