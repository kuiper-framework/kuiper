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

namespace kuiper\swoole;

use Dotenv\Dotenv;
use kuiper\di\attribute\Command;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilder;
use kuiper\di\ContainerFactoryInterface;
use function kuiper\helper\env;
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

    private ?ContainerInterface $container = null;

    private ?string $configFile = null;

    private ?Properties $config = null;

    private static ?Application $INSTANCE = null;

    /**
     * Application constructor.
     *
     * @param ContainerFactoryInterface|callable|null $containerFactory
     */
    final private function __construct(
        private readonly string $basePath,
        ContainerFactoryInterface|callable $containerFactory = null)
    {
        $this->containerFactory = $containerFactory;
        $this->loadConfig();
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

    public static function create(ContainerFactoryInterface|callable $containerFactory = null): self
    {
        $app = new static(defined('APP_PATH') ? APP_PATH : self::detectBasePath(), $containerFactory);
        if (null === self::$INSTANCE) {
            self::setInstance($app);
        }

        return $app;
    }

    public static function run(ContainerFactoryInterface|callable $containerFactory = null): int
    {
        $self = static::create($containerFactory);

        return $self->createApp()->run();
    }

    protected function loadConfig(): void
    {
        [$configFile, $properties] = $this->parseArgv();
        if (null !== $configFile) {
            if (!is_readable($configFile)) {
                throw new \InvalidArgumentException("config file '$configFile' is not readable");
            }
            $this->configFile = $configFile;
            $this->config = $this->parseConfig($configFile);
        } else {
            $this->config = Properties::create();
        }

        $this->addDefaultConfig();
        $this->addCommandLineOptions($properties);
        $this->loadEnv();
        $configFile = $this->config->getString('application.php_config_file', $this->getBasePath().'/src/config.php');
        if (file_exists($configFile)) {
            $this->config->merge(require $configFile);
        }
        $this->config->replacePlaceholder(static function (string $key) {
            return !str_starts_with($key, 'ENV.');
        });
    }

    /**
     * Parse config file.
     *
     * @param string $configFile
     *
     * @return Properties
     */
    protected function parseConfig(string $configFile): Properties
    {
        $config = parse_ini_file($configFile);
        if (false === $config) {
            throw new \InvalidArgumentException("Cannot read config from $configFile");
        }

        $properties = Properties::create();
        foreach ($config as $key => $value) {
            $properties->set($key, $value);
        }

        return $properties;
    }

    protected function addCommandLineOptions(array $properties): void
    {
        foreach ($properties as $i => $value) {
            if (!str_starts_with($value, 'application.')) {
                $value = 'application.'.$value;
                $properties[$i] = $value;
            }
        }
        $define = parse_ini_string(implode("\n", $properties));
        if (is_array($define)) {
            foreach ($define as $key => $value) {
                $this->config->set($key, $value ?? null);
            }
        }
    }

    /**
     * Create default config.
     */
    protected function addDefaultConfig(): void
    {
        $this->config->mergeIfNotExists([
            'application' => [
                'env' => env('ENV', 'prod'),
                'name' => 'app',
                'base_path' => $this->getBasePath(),
                'logging' => [
                    'path' => $this->getBasePath().'/logs',
                ],
            ],
            'ENV' => array_merge($_ENV, $_SERVER),
        ]);
    }

    /**
     * Load env file.
     *
     * @param array $envFiles
     */
    protected function loadEnv(array $envFiles = []): void
    {
        if (!class_exists(Dotenv::class)) {
            return;
        }
        $staging = $this->config->get('application.env');
        $envFiles = array_merge($envFiles, ['.env', '.env.local', ".env.{$staging}", ".env.{$staging}.local"]);
        Dotenv::createImmutable($this->getBasePath(), $envFiles, false)
            ->safeLoad();
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

    /**
     * @return string|null
     */
    public function getConfigFile(): ?string
    {
        return $this->configFile;
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
            $pos = strrpos(__DIR__, '/vendor/');
            if (false !== $pos) {
                $basePath = dirname(Composer::detect(substr(__DIR__, 0, $pos)));
            } else {
                $basePath = env('APP_PATH');
            }
        }

        if (!isset($basePath)
            || !file_exists($basePath.'/vendor/autoload.php')
            || !file_exists($basePath.'/composer.json')) {
            throw new \InvalidArgumentException("Cannot detect project path, expected composer.json in $basePath");
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', $basePath);
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
        foreach (ComponentCollection::getComponents(Command::class) as $attribute) {
            $commandMap[$attribute->getName()] = static function () use ($container, $attribute): ConsoleCommand {
                /** @var ConsoleCommand $command */
                $command = $container->get($attribute->getComponentId());
                if (Text::isEmpty($command->getName())) {
                    $command->setName($attribute->getName());
                }

                return $command;
            };
        }

        return $commandMap;
    }

    protected function parseArgv(): array
    {
        $configFile = null;
        $properties = [];
        $argv = $_SERVER['argv'];
        $rest = [];
        while (null !== $token = array_shift($argv)) {
            if ('--' === $token) {
                $rest[] = $token;
                break;
            }
            if (str_starts_with($token, '--')) {
                $name = substr($token, 2);
                $pos = strpos($name, '=');
                if (false !== $pos) {
                    $value = substr($name, $pos + 1);
                    $name = substr($name, 0, $pos);
                }
                if ('config' === $name) {
                    $configFile = $value ?? array_shift($argv);
                } elseif ('define' === $name) {
                    $properties[] = $value ?? array_shift($argv);
                } else {
                    $rest[] = $token;
                }
            } elseif ('-' === $token[0] && 2 === strlen($token) && 'D' === $token[1]) {
                $properties[] = array_shift($argv);
            } else {
                $rest[] = $token;
            }
        }
        $_SERVER['argv'] = array_merge($rest, $argv);

        return [$configFile, $properties];
    }
}
