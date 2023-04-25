<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

interface FileStreamFactoryInterface
{
    /**
     * @param string $file
     *
     * @return FileStreamInterface
     */
    public function create(string $file): FileStreamInterface;
}
