<?php

declare(strict_types=1);

namespace kuiper\swoole\config;

use DI\Annotation\Inject;
use function DI\autowire;
use function DI\get;
use function DI\value;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\Application;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\swoole\task\DispatcherInterface;
use kuiper\swoole\task\Queue;
use kuiper\swoole\task\QueueInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FoundationConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $this->containerBuilder->addAwareInjection(AwareInjection::create(ContainerAwareInterface::class));
        $this->containerBuilder->addDefinitions(new PropertiesDefinitionSource($config));

        return [
            PropertyResolverInterface::class => value($config),
            QueueInterface::class => autowire(Queue::class),
            DispatcherInterface::class => get(QueueInterface::class),
        ];
    }

    /**
     * @Bean("coroutineEnabled")
     */
    public function coroutineEnabled(): bool
    {
        $config = Application::getInstance()->getConfig();
        if ($config->getBool('application.server.enable_php_server', false)) {
            return false;
        }

        return $config->getBool('application.swoole.enable_coroutine', true);
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
     * @Inject({"poolConfig" = "application.pool"})
     */
    public function poolFactory(?array $poolConfig, LoggerFactoryInterface $loggerFactory, EventDispatcherInterface $eventDispatcher): PoolFactoryInterface
    {
        return new PoolFactory($this->coroutineEnabled(),
            $poolConfig ?? [], $loggerFactory->create(PoolFactory::class), $eventDispatcher);
    }
}
