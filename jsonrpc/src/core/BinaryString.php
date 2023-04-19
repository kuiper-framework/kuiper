<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\core;

use JsonSerializable;
use Stringable;

class BinaryString implements JsonSerializable, Stringable
{
    public function __construct(private readonly string $data)
    {
    }

    public function jsonSerialize(): string
    {
        return base64_encode($this->data);
    }

    public function __toString(): string
    {
        return $this->data;
    }
}
