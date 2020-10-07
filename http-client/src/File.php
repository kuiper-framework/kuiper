<?php

declare(strict_types=1);

namespace kuiper\http\client;

class File
{
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $name;

    /**
     * File constructor.
     *
     * @param string $name
     */
    public function __construct(string $path, string $name = null)
    {
        $this->path = $path;
        $this->name = $name ?? basename($path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
