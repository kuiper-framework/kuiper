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

namespace kuiper\swoole\config;

use DI\Attribute\Inject;
use kuiper\swoole\attribute\BootstrapConfiguration;
use Psr\EventDispatcher\EventDispatcherInterface;
use function DI\autowire;
use function DI\get;
use function DI\value;
use kuiper\di\attribute\Bean;
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
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[BootstrapConfiguration]
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

    #[Bean('coroutineEnabled')]
    public function coroutineEnabled(): bool
    {
        $config = Application::getInstance()->getConfig();
        if ($config->getBool('application.server.enable_php_server', false)) {
            return false;
        }

        return $config->getBool('application.swoole.enable_coroutine', true);
    }

    #[Bean]
    public function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

    #[Bean]
    public function poolFactory(
        #[Inject('application.pool')] ?array $poolConfig,
        LoggerFactoryInterface $loggerFactory,
        EventDispatcherInterface $eventDispatcher): PoolFactoryInterface
    {
        $poolFactory = new PoolFactory($this->coroutineEnabled(), $poolConfig ?? []);
        $poolFactory->setLogger($loggerFactory->create(PoolFactory::class));
        $poolFactory->setEventDispatcher($eventDispatcher);

        return $poolFactory;
    }
}
