<?php

declare(strict_types=1);

namespace kuiper\http\client;

class Request
{
    /**
     * @var array
     */
    private $options;

    /**
     * Request constructor.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
