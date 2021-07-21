<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

use kuiper\helper\JsonSerializableTrait;

class User implements \JsonSerializable
{
    use JsonSerializableTrait;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): User
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): User
    {
        $this->name = $name;

        return $this;
    }
}
