<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\StreamFactoryInterface;

class FileStreamFactory implements FileStreamFactoryInterface
{
    public function __construct(private readonly StreamFactoryInterface $streamFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $file): FileStreamInterface
    {
        return new FileStream($file, $this->streamFactory->createStreamFromFile($file));
    }
}
