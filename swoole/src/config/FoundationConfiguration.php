<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use DI\Annotation\Inject;
use function DI\autowire;
use DI\Definition\FactoryDefinition;
use DI\Definition\ObjectDefinition;
use function DI\get;
use function DI\value;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\event\EventDispatcherAwareInterface;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactory;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\annotation\PooledAnnotationReader;
use kuiper\swoole\Application;
use kuiper\swoole\monolog\CoroutineIdProcessor;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\task\DispatcherInterface;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FoundationConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->containerBuilder->addAwareInjection(new AwareInjection(
            LoggerAwareInterface::class,
            'setLogger',
            static function (ObjectDefinition $definition): array {
                $name = $definition->getName().'.logger';
                $class = $definition->getClassName();
                $loggerDefinition = new FactoryDefinition(
                    $name, static function (LoggerFactoryInterface $loggerFactory) use ($class): LoggerInterface {
                        return $loggerFactory->create($class);
                    });

                return [$loggerDefinition];
            }));
        $this->containerBuilder->addAwareInjection(AwareInjection::create(ContainerAwareInterface::class));
        $this->containerBuilder->addAwareInjection(AwareInjection::create(EventDispatcherAwareInterface::class));
        $config = Application::getInstance()->getConfig();
        $this->containerBuilder->addDefinitions(new PropertiesDefinitionSource($config));

        return [
            PropertyResolverInterface::class => value($config),
            QueueInterface::class => autowire(Queue::class),
            DispatcherInterface::class => get(QueueInterface::class),
            PsrEventDispatcher::class => get(EventDispatcherInterface::class),
            EventDispatcherInterface::class => autowire(EventDispatcher::class),
        ];
    }

    /**
     * @Bean("applicationName")
     */
    public function applicationName(): string
    {
        return Application::getInstance()->getConfig()->getString('application.name', 'app');
    }

    /**
     * @Bean
     * @Inject({"name": "applicationName"})
     */
    public function consoleApplication(string $name): ConsoleApplication
    {
        return new ConsoleApplication($name);
    }

    /**
     * @Bean
     */
    public function annotationReader(PoolFactoryInterface $poolFactory): AnnotationReaderInterface
    {
        return new PooledAnnotationReader($poolFactory);
    }

    /**
     * @Bean("coroutineEnabled")
     */
    public function coroutineEnabled(): bool
    {
        return !Application::getInstance()
            ->getConfig()
            ->getBool('application.server.enable-php-server', false);
    }

    /**
     * @Bean()
     */
    public function validator(AnnotationReaderInterface $annotationReader): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAnnotationMapping($annotationReader)
            ->getValidator();
    }

    /**
     * @Bean()
     */
    public function logger(LoggerFactoryInterface $loggerFactory): LoggerInterface
    {
        return $loggerFactory->create();
    }

    /**
     * @Bean()
     * @Inject({"name": "applicationName"})
     */
    public function loggerFactory(ContainerInterface $container, string $name): LoggerFactoryInterface
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'root' => [
                            'console' => true,
                            'level' => 'info',
                        ],
                    ],
                    'level' => [
                        'kuiper\\swoole' => 'info',
                    ],
                ],
            ],
        ]);
        $loggingConfig = $config->get('application.logging', []);
        $loggingConfig['loggers']['root'] = $this->createRootLogger($name, $loggingConfig);

        return new LoggerFactory($container, $loggingConfig);
    }

    /**
     * @Bean()
     * @Inject({"poolConfig" = "application.pool"})
     */
    public function poolFactory(?array $poolConfig, LoggerFactoryInterface $loggerFactory, EventDispatcherInterface $eventDispatcher): PoolFactoryInterface
    {
        return new PoolFactory($this->coroutineEnabled(),
            $poolConfig ?? [], $loggerFactory->create(PoolFactory::class), $eventDispatcher);
    }

    /**
     * @param string $name
     * @param array  $config
     *
     * @return array
     */
    protected function createRootLogger(string $name, array $config): array
    {
        $rootLoggerConfig = $config['loggers']['root'] ?? [];
        $loggerLevelName = strtoupper($rootLoggerConfig['level'] ?? 'error');

        $loggerLevel = constant(Logger::class.'::'.$loggerLevelName);
        if (!isset($loggerLevel)) {
            throw new \InvalidArgumentException("Unknown logger level '{$loggerLevelName}'");
        }
        $handlers = [];
        if (!empty($rootLoggerConfig['console'])) {
            $handlers[] = [
                'handler' => [
                    'class' => StreamHandler::class,
                    'constructor' => [
                        'stream' => 'php://stderr',
                        'level' => $loggerLevel,
                    ],
                ],
                'formatter' => [
                    'class' => LineFormatter::class,
                    'constructor' => [
                        'allowInlineLineBreaks' => true,
                    ],
                ],
            ];
        }
        if (isset($config['path'])) {
            $handlers[] = [
                'handler' => [
                    'class' => StreamHandler::class,
                    'constructor' => [
                        'stream' => $config['path'].'/default.log',
                        'level' => $loggerLevel,
                    ],
                ],
            ];

            $handlers[] = [
                'handler' => [
                    'class' => StreamHandler::class,
                    'constructor' => [
                        'stream' => $config['path'].'/error.log',
                        'level' => Logger::ERROR,
                    ],
                ],
            ];
        }

        return [
            'name' => $name,
            'handlers' => $handlers,
            'processors' => [
                CoroutineIdProcessor::class,
            ],
        ];
    }
}
