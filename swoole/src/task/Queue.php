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

namespace kuiper\swoole\task;

use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\swoole\attribute\TaskProcessor;
use kuiper\swoole\event\TaskEvent;
use kuiper\swoole\exception\TaskProcessorNotFoundException;
use kuiper\swoole\server\ServerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Queue implements QueueInterface, DispatcherInterface, LoggerAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    protected const TAG = '[' . __CLASS__ . '] ';

    /**
     * @var ProcessorInterface[]
     */
    private array $processors = [];

    public function __construct(private readonly ServerInterface $server)
    {
        $this->setLogger(\kuiper\logger\Logger::nullLogger());
    }

    /**
     * {@inheritdoc}
     */
    public function put(TaskInterface $task, int $workerId = -1, callable $onFinish = null): int
    {
        $this->getProcessor($task);

        $taskId = $this->server->task($task, $workerId, $onFinish);

        return is_int($taskId) ? $taskId : 0;
    }

    public function registerProcessor(string $taskClass, string|ProcessorInterface $handler): void
    {
        if (is_string($handler)) {
            if (!isset($this->container)) {
                throw new \InvalidArgumentException('container not set');
            }
            $handler = $this->container->get($handler);
        }
        if (!($handler instanceof ProcessorInterface)) {
            throw new \InvalidArgumentException("task handler '" . get_class($handler) . "' should implement " . ProcessorInterface::class);
        }
        $this->processors[$taskClass] = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(TaskEvent $event): void
    {
        try {
            /** @var TaskEventAwareInterface&TaskInterface $task */
            $task = $event->getData();
            $task->setTaskEvent($event);
            $processor = $this->getProcessor($task);

            $result = $processor->process($task);
            if (isset($result)) {
                $this->server->finish($result);
            }
        } catch (\Exception $e) {
            $this->logger->error(static::TAG . 'dispatch error', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function getProcessor(object $task): ProcessorInterface
    {
        $taskClass = get_class($task);
        if (isset($this->processors[$taskClass])) {
            return $this->processors[$taskClass];
        }

        $reflectionClass = new \ReflectionClass($taskClass);
        $attributes = $reflectionClass->getAttributes(TaskProcessor::class);
        if (count($attributes) > 0) {
            /** @var TaskProcessor $attribute */
            $attribute = $attributes[0]->newInstance();
            $this->registerProcessor($taskClass, $attribute->getName());
            return $this->processors[$taskClass];
        }

        $handler = $taskClass . 'Processor';
        if (class_exists($handler)) {
            $this->registerProcessor($taskClass, $handler);

            return $this->processors[$taskClass];
        }

        throw new TaskProcessorNotFoundException('Cannot find task processor for task ' . $taskClass);
    }
}
