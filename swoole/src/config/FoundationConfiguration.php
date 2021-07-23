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
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\task\DispatcherInterface;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
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
        ];
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
     * @Inject({"loggingConfig" = "application.logging"})
     */
    public function loggerFactory(ContainerInterface $container, ?array $loggingConfig): LoggerFactoryInterface
    {
        if (!isset($loggingConfig['loggers']['root'])) {
            $loggingConfig['loggers']['root'] = ['console' => true];
        }

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
}
