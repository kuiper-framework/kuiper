<?php

declare(strict_types=1);

namespace kuiper\logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerFactory implements LoggerFactoryInterface
{
    public const ROOT = '__root';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $logLevels;
    /**
     * @var string
     */
    private $defaultLevel;

    /**
     * level 配置:
     * [
     *    '__root' => 'INFO',
     *    'com' => [
     *         '__root' => 'INFO',
     *         'github' => [
     *         ]
     *    ]
     * ]
     * LoggerFactory constructor.
     */
    public function __construct(LoggerInterface $logger, array $logLevels = [], string $defaultLevel = LogLevel::ERROR)
    {
        $this->logger = $logger;
        $this->logLevels = $logLevels;
        $this->defaultLevel = $defaultLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $className): LoggerInterface
    {
        return new Logger($this->logger, $this->getLogLevel($className));
    }

    private function getLogLevel(string $className): string
    {
        $parts = explode('\\', trim($className, '\\'));

        $i = 0;
        $logLevel = $this->logLevels;
        while (isset($parts[$i], $logLevel[$parts[$i]])
            && is_array($logLevel[$parts[$i]])) {
            $logLevel = $logLevel[$parts[$i]];
            ++$i;
        }
        if (is_array($logLevel)) {
            $logLevel = $logLevel[self::ROOT] ?? '';
        }

        return Logger::getLevel($logLevel) ? $logLevel : $this->defaultLevel;
    }
}
