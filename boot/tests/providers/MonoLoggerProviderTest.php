<?php

namespace kuiper\boot\providers;

use kuiper\boot\Application;
use kuiper\boot\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class MonoLoggerProviderTest extends TestCase
{
    public function testLogger()
    {
        $app = new Application();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    MonologProvider::class,
                ],
            ],
        ]);
        $app->bootstrap();

        $logger = $app->get(LoggerInterface::class);
        // print_r($logger);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testLogging()
    {
        $app = new Application();
        $app->getSettings()->merge([
            'app' => [
                'providers' => [
                    MonologProvider::class,
                ],
            ],
            'logging' => [
                'default' => [
                    'file' => '/dev/null',
                ],
                'AccessLogger' => [
                    'file' => 'access.log',
                ],
            ],
        ]);
        $app->bootstrap();

        $logger = $app->get(LoggerInterface::class);
        // print_r($logger);
        $this->assertEquals('/dev/null', $this->getLoggerHandlerUrl($logger));

        $this->assertEquals('access.log', $this->getLoggerHandlerUrl($app->get('logger.AccessLogger')));
    }

    private function getLoggerHandlerUrl(Logger $logger)
    {
        $handler = $logger->getHandlers()[0];
        if ($handler instanceof StreamHandler) {
            return $handler->getUrl();
        }

        return null;
    }
}
