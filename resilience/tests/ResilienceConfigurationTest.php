<?php

declare(strict_types=1);

namespace kuiper\resilience;

use function DI\autowire;
use function DI\value;
use kuiper\di\ContainerBuilder;
use kuiper\logger\LoggerConfiguration;
use kuiper\resilience\circuitbreaker\CircuitBreakerFactory;
use kuiper\resilience\core\TryCall;
use kuiper\resilience\retry\RetryFactory;
use kuiper\swoole\Application;
use kuiper\swoole\config\FoundationConfiguration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ResilienceConfigurationTest extends TestCase
{
    private array $events = [];

    public function testRetry(): void
    {
        $container = $this->createContainer();
        $retry = $container->get(RetryFactory::class)->create('test');
        $callGenerator = static function (int $times) {
            if ($times > 0) {
                return static function () {
                };
            }

            return static function () {
                throw new RuntimeException();
            };
        };
        $result = TryCall::call([$retry, 'call'], $callGenerator(0));
        $this->assertTrue($result->isFailure());
        $this->assertCount(3, $this->events);

        $this->events = [];
        $result = TryCall::call([$retry, 'call'], $callGenerator(1));
        $this->assertFalse($result->isFailure());
        $this->assertCount(0, $this->events);
    }

    public function testCircuitBreaker(): void
    {
        $container = $this->createContainer();
        Application::getInstance()->getConfig()->set('application.client.circuitbreaker.default', [
            'minimumNumberOfCalls' => 2,
            'permittedNumberOfCallsInHalfOpenState' => 1,
            'waitIntervalInOpenState' => 50,
        ]);
        $breaker = $container->get(CircuitBreakerFactory::class)->create('test');
        $callGenerator = static function (int $times) {
            if ($times > 2) {
                return static function () {
                };
            }

            return static function () {
                throw new RuntimeException();
            };
        };

        $resultList = [];
        $exceptions = [];
        foreach (range(0, 10) as $i) {
            $result = TryCall::call([$breaker, 'call'], $callGenerator($i));
            $resultList[] = $result->isFailure();
            $exceptions[] = null === $result->getException() ? null : get_class($result->getException());

            usleep(10000);
        }
        $this->assertTrue($resultList[0]);
        $this->assertFalse($resultList[count($resultList) - 1]);
//        var_export($resultList);
//        var_export($exceptions);
//        var_export(count($this->events));
    }

    /**
     * @return array
     */
    private function createContainer(): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $builder->addDefinitions([
            EventDispatcherInterface::class => value($eventDispatcher),
            \Symfony\Component\EventDispatcher\EventDispatcherInterface::class => autowire(EventDispatcher::class),
        ]);
        $builder->addConfiguration(new LoggerConfiguration());
        $builder->addConfiguration(new FoundationConfiguration());
        $builder->addConfiguration(new ResilienceConfiguration());
        $this->events = [];
        $eventDispatcher->shouldReceive('dispatch')
            ->with(\Mockery::on(function ($event) {
                $this->events[] = $event;

                return true;
            }));

        $app = Application::create(static function () use ($builder) {
            return $builder->build();
        });

        return $app->getContainer();
    }
}
