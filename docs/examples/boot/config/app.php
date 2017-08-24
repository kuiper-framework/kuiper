<?php

return [
    'providers' => [
        kuiper\boot\providers\ConsoleApplicationProvider::class,
        kuiper\boot\providers\TwigViewProvider::class,
        kuiper\boot\providers\WebApplicationProvider::class,
    ],
    'commands' => [
        app\commands\CreateUserCommand::class,
    ],
    'base_path' => realpath(__DIR__.'/..'),
    'runtime_path' => '{app.base_path}/runtime',
    'views_path' => '{app.base_path}/views',
];
