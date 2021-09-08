<?php

declare(strict_types=1);

namespace kuiper\swoole\task;

class CallbackProcessor implements ProcessorInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * CallbackProcessor constructor.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function process(TaskInterface $task)
    {
        return call_user_func($this->callback, $task);
    }
}
