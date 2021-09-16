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

namespace kuiper\swoole\livereload;

interface FileWatcherInterface
{
    /**
     * Add a file path to be monitored for changes by this watcher.
     */
    public function addPath(string $path): void;

    /**
     * Returns file paths for files that changed since last read.
     *
     * @return string[]
     */
    public function getChangedPaths(): array;

    public function close(): void;
}
