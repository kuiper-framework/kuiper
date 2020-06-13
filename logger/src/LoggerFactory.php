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
    private $rootLevel;

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
    public function __construct(LoggerInterface $logger, array $logLevels = [], string $rootLevel = LogLevel::ERROR)
    {
        $this->logger = $logger;
        $this->logLevels = self::createLogLevels($logLevels);
        $this->rootLevel = $rootLevel;
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
            $logLevel = $logLevel[self::ROOT] ?? null;
        }

        return is_string($logLevel) && Logger::getLevel($logLevel) ? $logLevel : $this->rootLevel;
    }

    public static function createLogLevels(array $config): array
    {
        $logLevels = [];
        foreach ($config as $name => $level) {
            if (!is_string($name)) {
                throw new \InvalidArgumentException('Log level config should only contain string key');
            }
            if (is_array($level)) {
                self::validateLogLevels($level, $name);
                $logLevels[$name] = $level;
            } else {
                if (null === Logger::getLevel($level)) {
                    throw new \InvalidArgumentException("Invalid log level '$level' for '$name'");
                }
                $logLevel = &$logLevels;
                $parts = explode('.', $name);
                while ($parts) {
                    $first = array_shift($parts);
                    if (self::ROOT === $first) {
                        throw new \InvalidArgumentException("The namespace '$name' is invalid");
                    }
                    if (!isset($logLevel[$first])) {
                        $logLevel[$first] = [];
                    }
                    $logLevel = &$logLevel[$first];
                }
                $logLevel[self::ROOT] = $level;
            }
        }

        return $logLevels;
    }

    private static function validateLogLevels(array $logLevels, string $prefix): void
    {
        foreach ($logLevels as $item => $level) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException('Log level config should only contain string key');
            }
            if (self::ROOT === $item) {
                if (null === Logger::getLevel($level)) {
                    throw new \InvalidArgumentException("Invalid log level '$level' for '$prefix.$item'");
                }
            } elseif (is_array($level)) {
                self::validateLogLevels($level, $prefix.'.'.$item);
            } else {
                throw new \InvalidArgumentException('Invalid log level config');
            }
        }
    }
}
