<?php

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
