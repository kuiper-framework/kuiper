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
use Exception;
use InvalidArgumentException;
use kuiper\di\attribute\Command;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilder;
use kuiper\di\ContainerFactoryInterface;
use kuiper\event\EventDispatcher;
use kuiper\event\EventRegistryInterface;

use function kuiper\helper\env;

use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\swoole\attribute\BootstrapConfiguration;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;

class Application
{
    private const CONFIG_BOOTSTRAPPING = 'application.bootstrapping';

    /**
     * @var ContainerFactoryInterface|callable|null
     */
    private $containerFactory;

    private EventDispatcherInterface $eventDispatcher;

    private ?ContainerInterface $container = null;

    private ?ContainerInterface $bootstrapContainer = null;

    private ?string $configFile = null;

    private ?Properties $config = null;

    private ?Properties $configBackup = null;

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
        $this->eventDispatcher = new EventDispatcher();
        $this->loadConfig();
    }

    public static function hasInstance(): bool
    {
        return null !== self::$INSTANCE;
    }

    public static function getInstance(): self
    {
        if (null === self::$INSTANCE) {
            throw new RuntimeException('Application instance not created, forgot to call Application::create()?');
        }

        return self::$INSTANCE;
    }

    public static function setInstance(Application $application): void
    {
        self::$INSTANCE = $application;
    }

    public static function create(ContainerFactoryInterface|callable $containerFactory = null): self
    {
        $basePath = defined('APP_PATH') ? APP_PATH : self::detectBasePath();
        $app = new static($basePath, $containerFactory);

        if (null === self::$INSTANCE) {
            self::setInstance($app);
        }

        return $app;
    }

    /**
     * @throws Exception
     */
    public static function run(ContainerFactoryInterface|callable $containerFactory = null): int
    {
        $self = static::create($containerFactory);

        return $self->createConsoleApplication()->run();
    }

    protected function loadConfig(): void
    {
        [$configFile, $properties] = $this->parseArgv();
        if (null !== $configFile) {
            if (!is_readable($configFile)) {
                throw new InvalidArgumentException("config file '$configFile' is not readable");
            }
            $this->configFile = $configFile;
            $this->config = $this->parseConfig($configFile);
        } else {
            $this->config = Properties::create();
        }

        $this->addDefaultConfig();
        $this->addCommandLineOptions($properties);
        $this->loadEnv();
        $configFile = $this->config->getString('application.php_config_file');
        if (file_exists($configFile)) {
            $this->config->merge(require $configFile);
        }
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
            throw new InvalidArgumentException("Cannot read config from $configFile");
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
                'env' => env('APP_ENV', 'prod'),
                'enable_bootstrap_container' => 'true' === env('APP_ENABLE_BOOTSTRAP_CONTAINER', 'true'),
                'name' => env('APP_NAME', 'app'),
                'php_config_file' => env('APP_PHP_CONFIG_FILE', $this->getBasePath().'/src/config.php'),
                'base_path' => $this->getBasePath(),
                'logging' => [
                    'path' => env('LOGGING_PATH', $this->getBasePath().'/logs'),
                    'level' => [
                        'kuiper\\swoole' => 'info',
                    ],
                ],
                'server' => [
                    'enable_php_server' => 'true' === env('SERVER_ENABLE_PHP_SERVER'),
                    'http_factory' => env('SERVER_HTTP_FACTOR', class_exists(ServerRequestFactory::class) ? 'diactoros' : 'guzzle'),
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

    public function getBootstrapContainer(): ContainerInterface
    {
        if (!$this->isBootstrapContainerEnabled()) {
            throw new InvalidArgumentException('Cannot create server start container');
        }
        if (null === $this->bootstrapContainer) {
            $this->bootstrapContainer = $this->createBootstrapContainer();
        }

        return $this->bootstrapContainer;
    }

    public function isBootstrapping(): bool
    {
        return $this->config->getBool(self::CONFIG_BOOTSTRAPPING);
    }

    public function isBootstrapContainerEnabled(): bool
    {
        return $this->isServerStartCommand()
            && $this->config->getBool('application.enable_bootstrap_container');
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
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

    /**
     * @return ConsoleApplication
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     * @deprecated
     */
    public function createApp(): ConsoleApplication
    {
        return $this->createConsoleApplication();
    }

    public function createConsoleApplication(): ConsoleApplication
    {
        if ($this->isBootstrapContainerEnabled()) {
            $container = $this->getBootstrapContainer();
        } else {
            $container = $this->getContainer();
        }
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
            throw new InvalidArgumentException("Cannot detect project path, expected composer.json in $basePath");
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', $basePath);
        }

        return $basePath;
    }

    protected function isServerStartCommand(): bool
    {
        $command = $_SERVER['argv'][1] ?? null;

        return !isset($command) || ServerStartCommand::NAME === $command;
    }

    protected function createBootstrapContainer(): ContainerInterface
    {
        $this->configBackup = Properties::create($this->config->toArray());
        $this->config->set(self::CONFIG_BOOTSTRAPPING, true);
        $projectPath = $this->basePath;
        if (!file_exists($projectPath.'/vendor/autoload.php')
            || !file_exists($projectPath.'/composer.json')) {
            throw new InvalidArgumentException("Cannot detect project path, expected composer.json in $projectPath");
        }
        $builder = new ContainerBuilder();
        $builder->setClassLoader(require $projectPath.'/vendor/autoload.php');

        $composerJson = json_decode(file_get_contents($projectPath.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $configFile = $projectPath.'/'.($composerJson['extra']['kuiper']['config-file'] ?? 'config/container.php');
        if (file_exists($configFile)) {
            $config = require $configFile;

            if (!empty($config['configuration'])) {
                foreach ($config['configuration'] as $configurationBean) {
                    $reflectionClass = new ReflectionClass($configurationBean);
                    if (0 === count($reflectionClass->getAttributes(BootstrapConfiguration::class))) {
                        continue;
                    }
                    if (is_string($configurationBean)) {
                        $configurationBean = new $configurationBean();
                    }
                    $builder->addConfiguration($configurationBean);
                }
            }
        }

        return $builder->build();
    }

    protected function createContainer(): ContainerInterface
    {
        if ($this->eventDispatcher instanceof EventRegistryInterface && $this->isBootstrapContainerEnabled()) {
            $this->getBootstrapContainer();
            $this->eventDispatcher->reset();
            $this->config = $this->configBackup;
        }
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
                throw new InvalidArgumentException('application.commands should be an array');
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
