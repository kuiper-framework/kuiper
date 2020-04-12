<?php

declare(strict_types=1);

namespace kuiper\di;

use function DI\autowire;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AwareAutowiringTest extends TestCase
{
    public function testAware()
    {
        $builder = new ContainerBuilder();
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $logger = new NullLogger();
        $builder->addDefinitions([
            LoggerInterface::class => $logger,
            'fooWithLogger' => autowire(Foo::class)
                ->method('setLogger', new NullLogger()),
        ]);
        $container = $builder->build();
        $foo = $container->get(Foo::class);
        $this->assertSame($logger, $foo->getLogger());

        $fooWithLogger = $container->get('fooWithLogger');
        $this->assertNotSame($logger, $fooWithLogger->getLogger());
    }
}

class Foo implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
