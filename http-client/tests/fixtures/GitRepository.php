<?php

declare(strict_types=1);

namespace kuiper\http\client\fixtures;

use kuiper\helper\JsonSerializableTrait;

class GitRepository implements \JsonSerializable
{
    use JsonSerializableTrait;

    /**
     * @var string
     */
    private $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
