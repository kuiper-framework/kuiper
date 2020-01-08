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
     * @var string
     *
     * @Required()
     */
    public $name;
}
