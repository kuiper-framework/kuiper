<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\StreamInterface;

interface FileStreamInterface extends StreamInterface
{
    public function getFileName(): string;
}
