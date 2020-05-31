<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\annotation\Column;
use kuiper\db\annotation\Embeddable;

/**
 * @Embeddable()
 */
class DoorId
{
    /**
     * @Column("door_code")
     *
     * @var string
     */
    private $value;

    /**
     * DoorId constructor.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
