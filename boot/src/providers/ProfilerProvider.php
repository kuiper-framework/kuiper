<?php

namespace kuiper\boot\providers;

use kuiper\boot\Provider;
use kuiper\di;
use kuiper\web\Events;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use winwin\profiler\AsciiRenderer;
use winwin\profiler\Profiler;
use winwin\profiler\RendererInterface;

class ProfilerProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            RendererInterface::class => di\factory([$this, 'provideProfilerRenderer']),
        ]);
    }

    public function boot()
    {
        if ($this->settings['app.profiler_enabled']) {
            $eventDispatcher = $this->app->getEventDispatcher();
            $eventDispatcher->addListener(Events::BEGIN_REQUEST, function () {
                Profiler::getInstance()->enable()->reset();
            });
            $eventDispatcher->addListener(Events::END_REQUEST, function () {
                Profiler::getInstance()
                    ->setRenderer($this->app->get(RendererInterface::class))
                    ->render();
            });
        }
    }

    public function provideProfilerRenderer()
    {
        $renderer = new AsciiRenderer();
        $logger = new Logger('Profiler');
        $logger->pushHandler($handler = new StreamHandler($this->settings['app.runtime_path'].'/profile.log'));
        $handler->setFormatter(new LineFormatter(null, null, true));
        $renderer->setLogger($logger);

        return $renderer;
    }
}
