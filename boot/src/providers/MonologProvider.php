<?php

namespace kuiper\boot\providers;

use kuiper\boot\Provider;
use kuiper\di;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonologProvider extends Provider
{
    private static $LOGGERS = [];

    public function register()
    {
        $this->services->addDefinitions([
            LoggerInterface::class => di\factory([$this, 'provideLogger']),
        ]);
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

        $logger = new Logger($settings['logger.name'] ?: $settings['app.name'] ?: 'unnamed');
        $logLevel = constant(Logger::class.'::'.strtoupper($settings['logger.level'] ?: 'debug'));
        if (isset($settings['logger.file'])) {
            $logFile = $settings['logger.file'];
        } else {
            $logFile = 'php://stderr';
        }
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));
        if (isset($settings['logger.error_file'])) {
            $logger->pushHandler(new StreamHandler($settings['logger.error_file'], Logger::ERROR));
        }
        $processors = $settings['logger.processors'];
        if (is_array($processors)) {
            $container = $this->app->getContainer();
            foreach ($processors as $processor) {
                $logger->pushProcessor($container->get($processor));
            }
        }

        self::pushLogger($logger);

        return $logger;
    }
}
