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
