<?php

namespace kuiper\boot\providers;

use kuiper\boot\Provider;
use kuiper\di;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonologProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            LoggerInterface::class => di\factory([$this, 'provideLogger']),
        ]);
    }

    public function provideLogger()
    {
        $settings = $this->app->getSettings();

        $logger = new Logger($settings['logger.name'] ?: $settings['app.name'] ?: 'unnamed');
        $logLevel = constant(Logger::class.'::'.strtoupper($settings['logger.level'] ?: 'debug'));
        if (isset($settings['logger.file'])) {
            $logFile = $this->template($settings['logger.file']);
        } else {
            $logFile = 'php://stderr';
        }
        $logger->pushHandler(new StreamHandler($logFile, $logLevel));
        if (isset($settings['logger.error_file'])) {
            $logger->pushHandler(new StreamHandler($this->template($settings['logger.error_file']), Logger::ERROR));
        }
        $processors = $settings['logger.processors'];
        if (is_array($processors)) {
            $container = $this->app->getContainer();
            foreach ($processors as $processor) {
                $logger->pushProcessor($container->get($processor));
            }
        }

        return $logger;
    }
}
