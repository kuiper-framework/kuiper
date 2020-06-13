<?php

declare(strict_types=1);

namespace kuiper\logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    private static $LEVELS = [
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $logLevel;

    public function __construct(LoggerInterface $logger, string $logLevel)
    {
        if (isset(self::$LEVELS[$logLevel])) {
            throw new \InvalidArgumentException("Unknown log level '$logLevel'");
        }
        $this->logger = $logger;
        $this->logLevel = self::$LEVELS[$logLevel];
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        if (self::$LEVELS[$level] <= $this->logLevel) {
            $this->logger->log($level, $message, $context);
        }
    }

    public static function getLevel(string $level): ?int
    {
        return self::$LEVELS[$level] ?? null;
    }
}
