<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

use kuiper\annotations\AnnotationReaderAwareInterface;
use kuiper\annotations\AnnotationReaderAwareTrait;
use kuiper\swoole\annotation\TaskProcessor;
use kuiper\swoole\ServerInterface;
use Psr\Container\ContainerInterface;

class Queue implements QueueInterface, DispatcherInterface, AnnotationReaderAwareInterface
{
    use AnnotationReaderAwareTrait;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ProcessorInterface[]
     */
    private $processors;

    public function __construct(ServerInterface $server, ContainerInterface $container)
    {
        $this->server = $server;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function put($task, int $workerId = -1, callable $onFinish = null): int
    {
        $this->getProcessor($task);

        return $this->server->getSwooleServer()->task($task, $workerId, $onFinish);
    }

    public function registerProcessor(string $taskClass, $handler): void
    {
        if (is_string($handler)) {
            $handler = $this->container->get($handler);
        }
        if (!($handler instanceof ProcessorInterface)) {
            throw new \InvalidArgumentException("task handler '".get_class($handler)."' should implement ".ProcessorInterface::class);
        }
        $this->processors[$taskClass] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($task): void
    {
        $result = $this->getProcessor($task)->process($task);
        if (isset($result)) {
            $this->server->getSwooleServer()->finish($result);
        }
    }

    public function getProcessor(object $task): ProcessorInterface
    {
        $taskClass = get_class($task);
        if (isset($this->processors[$taskClass])) {
            return $this->processors[$taskClass];
        }

        if ($this->annotationReader) {
            /** @var TaskProcessor $annotation */
            $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($taskClass), TaskProcessor::class);
            if ($annotation) {
                $this->registerProcessor($taskClass, $annotation->name);

                return $this->processors[$taskClass];
            }
        }

        $handler = $taskClass.'Processor';
        if (class_exists($handler)) {
            $this->registerProcessor($taskClass, $handler);

            return $this->processors[$taskClass];
        }

        throw new TaskProcessorNotFoundException('Cannot find task processor for task '.$taskClass);
    }
}
