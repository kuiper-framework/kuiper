<?php

namespace kuiper\boot\providers;

use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\GenericEvent as Event;

class ConsoleApplicationProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            ConsoleApplication::class => di\factory([$this, 'provideConsoleApplication']),
        ]);
    }

    public function provideConsoleApplication()
    {
        $app = new ConsoleApplication();
        $container = $this->app->getContainer();
        $commands = $this->settings['app.commands'];
        if ($commands) {
            foreach ($commands as $command) {
                $app->add($container->get($command));
            }
        }
        $this->app->getEventDispatcher()->dispatch(Events::BOOT_CONSOLE_APPLICATION, new Event($app));

        return $app;
    }
}
