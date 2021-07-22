<?php

declare(strict_types=1);

namespace kuiper\serializer\fixtures;

class Customer
{
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

    public function setId(?int $id): Customer
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Customer
    {
        $this->name = $name;

        return $this;
    }
}
