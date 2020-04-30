<?php

declare(strict_types=1);

namespace kuiper\swoole\annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class TaskProcessor
{
    /**
     * class name of the task processor (use as the id that looked up in container).
     *
     * @var string
     *
     * @Required()
     */
    public $name;
}
