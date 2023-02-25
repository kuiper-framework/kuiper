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

use InvalidArgumentException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class LoggerFactory implements LoggerFactoryInterface
{
    private const ROOT = '__root';

    private const ROOT_LOGGER = 'root';

    /**
     * @var LoggerInterface[]
     */
    private array $loggers;

    /**
     * @var array
     */
    private array $logLevels;
    /**
     * @var array
     */
    private array $logNames;

    public function __construct(private readonly ContainerInterface $container, array $loggingConfig)
    {
        if (!isset($loggingConfig['loggers'][self::ROOT_LOGGER])) {
            throw new InvalidArgumentException('loggers.root is required');
        }
        $this->createLoggers($loggingConfig['loggers']);
        $this->logLevels = self::createLogConfig($loggingConfig['level'] ?? [], [$this, 'validateLogLevels']);
        $this->logNames = self::createLogConfig($loggingConfig['logger'] ?? [], [$this, 'validateLogNames']);
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $className = null): LoggerInterface
    {
        $logLevel = $this->getLogLevel($className);
        $logger = $this->getLogger($className);

        return isset($logLevel) ? new Logger($logger, $logLevel) : $logger;
    }

    public function getLoggers(): array
    {
        return array_values($this->loggers);
    }

    private function getLogLevel(?string $className): ?string
    {
        if (!isset($className)) {
            return null;
        }

        return $this->getLogConfig($className, $this->logLevels);
    }

    private function getLogger(?string $className): LoggerInterface
    {
        if (!isset($className)) {
            return $this->loggers[self::ROOT_LOGGER];
        }
        $logName = $this->getLogConfig($className, $this->logNames);

        return $this->loggers[$logName] ?? $this->loggers[self::ROOT_LOGGER];
    }

    private function getLogConfig(string $className, array $logConfig): ?string
    {
        $parts = explode('\\', trim($className, '\\'));

        $i = 0;
        while (isset($parts[$i], $logConfig[$parts[$i]]) && is_array($logConfig[$parts[$i]])) {
            $logConfig = $logConfig[$parts[$i]];
            ++$i;
        }
        $config = is_array($logConfig) ? $logConfig[self::ROOT] ?? null : $logConfig;

        return is_string($config) ? $config : null;
    }

    public static function createLogConfig(array $config, callable $checker): array
    {
        $logConfig = [];
        foreach ($config as $name => $value) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Log config value should only contain string key');
            }
            if (is_array($value)) {
                $checker($value, $name);
                $logConfig[$name] = $value;
            } else {
                $checker([self::ROOT => $value], $name);
                $subConfig = &$logConfig;
                $parts = explode('\\', $name);
                while ($parts) {
                    $first = array_shift($parts);
                    if (self::ROOT === $first) {
                        throw new InvalidArgumentException("The namespace '$name' is invalid");
                    }
                    if (!isset($subConfig[$first])) {
                        $subConfig[$first] = [];
                    }
                    $subConfig = &$subConfig[$first];
                }
                $subConfig[self::ROOT] = $value;
            }
        }

        return $logConfig;
    }

    private function validateLogLevels(array $logLevels, string $prefix): void
    {
        foreach ($logLevels as $item => $level) {
            if (!is_string($item)) {
                throw new InvalidArgumentException('Log level should only contain string key');
            }
            if (self::ROOT === $item) {
                if (null === Logger::getLevel($level)) {
                    throw new InvalidArgumentException("Invalid log level '$level' for '$prefix.$item'");
                }
            } elseif (is_array($level)) {
                self::validateLogLevels($level, $prefix.'.'.$item);
            } else {
                throw new InvalidArgumentException('Invalid log level config');
            }
        }
    }

    private function validateLogNames(array $logNames, string $prefix): void
    {
        foreach ($logNames as $item => $name) {
            if (!is_string($item)) {
                throw new InvalidArgumentException('Log name should only contain string key');
            }
            if (self::ROOT === $item) {
                if (!isset($this->loggers[$name])) {
                    throw new InvalidArgumentException("Invalid log name '$name' for '$prefix.$item'");
                }
            } elseif (is_array($name)) {
                self::validateLogNames($name, $prefix.'.'.$item);
            } else {
                throw new InvalidArgumentException('Invalid log name config');
            }
        }
    }

    private function createLoggers(array $loggers): void
    {
        foreach ($loggers as $name => $logConfig) {
            try {
                $this->loggers[$name] = $this->createLogger($logConfig);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("invalid logger config for '$name'", 0, $e);
            }
        }
    }

    private function createLogger(array $settings): LoggerInterface
    {
        $logger = new \Monolog\Logger($settings['name'] ?? 'unnamed');
        // disable infinite logging loops, because coroutine reenter addRecord function
        $logger->useLoggingLoopDetection(false);
        $logLevel = constant(\Monolog\Logger::class.'::'.strtoupper($settings['level'] ?? 'error'));

        if (!empty($settings['console'])) {
            $logger->pushHandler(new StreamHandler('php://stderr', $logLevel));
        }
        if (isset($settings['file'])) {
            $logger->pushHandler($this->createFileHandler($settings['file'], $logLevel, $settings['rotate'] ?? null));
        }
        if (isset($settings['handlers'])) {
            foreach ($settings['handlers'] as $handlerSetting) {
                if ($handlerSetting['handler']) {
                    $logger->pushHandler($this->createHandler($handlerSetting));
                } else {
                    throw new InvalidArgumentException('handler is required');
                }
            }
        }
        if (isset($settings['processors']) && is_array($settings['processors'])) {
            foreach ($settings['processors'] as $processor) {
                $logger->pushProcessor($this->createObject($processor));
            }
        }

        return $logger;
    }

    private function createFileHandler(string $logFile, int $logLevel, ?int $maxFiles = null): StreamHandler
    {
        if (null !== $maxFiles) {
            return new RotatingFileHandler($logFile, $maxFiles, $logLevel);
        }

        return new StreamHandler($logFile, $logLevel);
    }

    private function createHandler(array $handlerSetting): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = $this->createObject($handlerSetting['handler']);
        if (isset($handlerSetting['formatter']) && $handler instanceof FormattableHandlerInterface) {
            /** @var FormatterInterface $formatter */
            $formatter = $this->createObject($handlerSetting['formatter']);
            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    private function createObject(string|array $definition): mixed
    {
        if (is_string($definition)) {
            return $this->container->get($definition);
        }
        if (isset($definition['class'])) {
            $class = $definition['class'];
            $args = $definition['constructor'] ?? [];
            if (!empty($args) && !isset($args[0])) {
                $args = $this->resolveConstructorParameters($class, $args);
            }

            return new $class(...$args);
        }
        throw new InvalidArgumentException('Invalid config');
    }

    private function resolveConstructorParameters(string $class, array $args): array
    {
        $reflectionClass = new ReflectionClass($class);
        $parameters = [];
        $constructor = $reflectionClass->getConstructor();
        if (isset($constructor)) {
            foreach ($constructor->getParameters() as $parameter) {
                if (array_key_exists($parameter->getName(), $args)) {
                    $parameters[] = $args[$parameter->getName()];
                } elseif ($parameter->isOptional()) {
                    $parameters[] = $parameter->getDefaultValue();
                } else {
                    throw new InvalidArgumentException("parameter {$parameter->getName()} of $class constructor is required");
                }
            }
        }

        return $parameters;
    }
}
