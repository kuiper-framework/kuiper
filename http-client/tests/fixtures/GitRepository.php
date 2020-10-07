<?php

declare(strict_types=1);

namespace kuiper\http\client\fixtures;

class GitRepository
{
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
