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
     */
    public function process(TaskInterface $task)
    {
        return call_user_func($this->callback, $task);
    }
}
