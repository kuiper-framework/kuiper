<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\di\annotation\Command;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilder;
use kuiper\di\ContainerFactoryInterface;
use kuiper\helper\Properties;
use kuiper\helper\Text;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class Application
{
    /**
     * @var ContainerFactoryInterface|callable|null
     */
    private $containerFactory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var Properties
     */
    private $config;

    /**
     * @var self
     */
    private static $INSTANCE;

    /**
     * Application constructor.
     *
     * @param ContainerFactoryInterface|callable|null $containerFactory
     */
    final private function __construct(string $basePath, $containerFactory = null)
    {
        $this->containerFactory = $containerFactory;
        $this->basePath = $basePath;
        $this->config = $this->loadConfig();
    }

    public static function getInstance(): self
    {
        if (null === self::$INSTANCE) {
            throw new \InvalidArgumentException('Call create first');
        }

        return self::$INSTANCE;
    }

    public static function setInstance(Application $application): void
    {
        self::$INSTANCE = $application;
    }

    /**
     * @param ContainerFactoryInterface|callable|null $containerFactory
     */
    public static function create($containerFactory = null): self
    {
        $serverApplication = new static(defined('APP_PATH') ? APP_PATH : self::detectBasePath(), $containerFactory);
        if (null === self::$INSTANCE) {
            self::setInstance($serverApplication);
        }

        return $serverApplication;
    }

    /**
     * @param ContainerFactoryInterface|callable|null $containerFactory
     *
     * @throws \Exception
     */
    public static function run($containerFactory = null): int
    {
        $self = static::create($containerFactory);

        return $self->createApp()->run();
    }

    protected function loadConfig(): Properties
    {
        $configFile = $this->basePath.'/src/config.php';
        if (file_exists($configFile)) {
            /* @noinspection PhpIncludeInspection */
            return Properties::create(require $configFile);
        }

        return Properties::create();
    }

    public function getContainer(): ContainerInterface
    {
        if (null === $this->container) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getConfig(): Properties
    {
        return $this->config;
    }

    public function createApp(): ConsoleApplication
    {
        $container = $this->getContainer();
        $app = $container->get(ConsoleApplication::class);
        $commandLoader = new FactoryCommandLoader($this->getCommandMap($container));
        $app->setCommandLoader($commandLoader);
        $defaultCommand = $this->getConfig()->getString('application.default_command');
        if ('' !== $defaultCommand) {
            $app->setDefaultCommand($defaultCommand);
        }

        return $app;
    }

    public static function detectBasePath(): string
    {
        if (defined('APP_PATH')) {
            $basePath = APP_PATH;
        } else {
            $libraryComposerJson = Composer::detect(__DIR__);
            $basePath = dirname($libraryComposerJson, 4);
            define('APP_PATH', $basePath);
        }

        if (!file_exists($basePath.'/vendor/autoload.php')
            || !file_exists($basePath.'/composer.json')) {
            throw new \InvalidArgumentException("Cannot detect project path, expected composer.json in $basePath");
        }

        return $basePath;
    }

    protected function createContainer(): ContainerInterface
    {
        if (null === $this->containerFactory) {
            return ContainerBuilder::create($this->basePath)
                ->build();
        }
        if ($this->containerFactory instanceof ContainerFactoryInterface) {
            return $this->containerFactory->create();
        }

        return call_user_func($this->containerFactory);
    }

    protected function getCommandMap(ContainerInterface $container): array
    {
        $factory = static function ($id) use ($container): callable {
            return static function () use ($container, $id) {
                return $container->get($id);
            };
        };
        $commandMap = [];
        $commands = $container->get('application.commands');
        if (null !== $commands) {
            if (!is_array($commands)) {
                throw new \InvalidArgumentException('application.commands should be an array');
            }
            foreach ($commands as $name => $id) {
                $commandMap[$name] = $factory($id);
            }
        }
        /** @var Command $annotation */
        foreach (ComponentCollection::getAnnotations(Command::class) as $annotation) {
            $commandMap[$annotation->name] = static function () use ($container, $annotation): ConsoleCommand {
                /** @var ConsoleCommand $command */
                $command = $container->get($annotation->getComponentId());
                if (Text::isEmpty($command->getName())) {
                    $command->setName($annotation->name);
                }

                return $command;
            };
        }

        return $commandMap;
    }
}
