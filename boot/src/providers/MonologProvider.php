<?php

namespace kuiper\boot\providers;

use kuiper\boot\Provider;
use kuiper\di;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * 日志配置项：
 * - name
 * - level
 * - file
 * - error_file
 * - allow_inline_line_breaks
 * - processors.
 *
 * Class MonologProvider
 */
class MonologProvider extends Provider
{
    private static $LOGGERS = [];

    public function register()
    {
        $loggers[LoggerInterface::class] = di\factory([$this, 'provideLogger']);
        if ($this->settings['logging']) {
            foreach ($this->settings['logging'] as $loggerName => $config) {
                if (in_array($loggerName, ['default', LoggerInterface::class])) {
                    continue;
                }
                $loggers['logger.'.$loggerName] = di\factory([$this, 'createLogger'], $config);
            }
        }
        $this->services->addDefinitions($loggers);
    }

    public static function pushLogger(Logger $logger)
    {
        self::$LOGGERS[] = $logger;
    }

    public static function reopen()
    {
        foreach (self::$LOGGERS as $logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof StreamHandler) {
                    $handler->close();
                }
            }
        }
    }

    public function provideLogger()
    {
        $settings = $this->app->getSettings();

        $config = [];
        foreach (['logger', 'logging.'.LoggerInterface::class, 'logging.default'] as $item) {
            if (isset($settings[$item])) {
                $config = $settings[$item];
            }
        }

        return $this->createLogger($config);
    }

    /**
     * @param array $settings
     *
     * @return LoggerInterface
     */
    public function createLogger(array $settings)
    {
        $logger = new Logger($settings['name'] ?? 'unnamed');
        $logLevel = constant(Logger::class.'::'.strtoupper($settings['level'] ?? 'debug'));
        $logFile = $settings['file'] ?? 'php://stderr';
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));
        if (isset($settings['error_file'])) {
            $logger->pushHandler(new StreamHandler($settings['error_file'], Logger::ERROR));
        }
        if (!empty($settings['allow_inline_line_breaks'])) {
            foreach ($logger->getHandlers() as $handler) {
                $handler->setFormatter(new LineFormatter(null, null, true));
            }
        }
        if (isset($settings['processors']) && is_array($settings['processors'])) {
            foreach ($settings['processors'] as $processor) {
                $logger->pushProcessor($this->app->get($processor));
            }
        }

        self::pushLogger($logger);

        return $logger;
    }
}
