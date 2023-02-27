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

namespace kuiper\logger;

use DI\Definition\FactoryDefinition;
use DI\Definition\ObjectDefinition;
use kuiper\swoole\attribute\BootstrapConfiguration;
use function DI\factory;
use kuiper\di\attribute\Bean;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\PropertyResolverInterface;
use kuiper\swoole\logger\CoroutineIdProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

#[BootstrapConfiguration]
class LoggerConfiguration implements DefinitionConfiguration
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

        return [
            LoggerInterface::class => factory(static function (LoggerFactoryInterface $loggerFactory): LoggerInterface {
                return $loggerFactory->create();
            }),
        ];
    }

    #[Bean]
    public function loggerFactory(ContainerInterface $container): LoggerFactoryInterface
    {
        $config = $container->get(PropertyResolverInterface::class);
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
        $loggingConfig['loggers']['root'] = $this->createRootLogger($container->get('application.name') ?? 'app', $loggingConfig);

        return new LoggerFactory($container, $loggingConfig);
    }

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

    public static function createAccessLogger(string $logFileName): array
    {
        return self::createLogger($logFileName, false);
    }

    public static function createJsonLogger(string $logFileName): array
    {
        return self::createLogger($logFileName, true);
    }

    private static function createLogger(string $logFileName, bool $messageOnly): array
    {
        $logger = [
            'handlers' => [
                [
                    'handler' => [
                        'class' => FileHandler::class,
                        'constructor' => [
                            'stream' => $logFileName,
                        ],
                    ],
                    'formatter' => [
                        'class' => LineFormatter::class,
                        'constructor' => [
                            'format' => $messageOnly ? "%message%\n" : "%message% %context% %extra%\n",
                        ],
                    ],
                ],
            ],
        ];
        if (!$messageOnly) {
            $logger['processors'] = [
                CoroutineIdProcessor::class,
            ];
        }

        return $logger;
    }
}
