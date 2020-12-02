<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\annotation\Column;
use kuiper\db\annotation\GeneratedValue;
use kuiper\db\annotation\Id;
use kuiper\db\annotation\NaturalId;
use kuiper\db\annotation\ShardKey;

class Item
{
    /**
     * @var int
     * @Id()
     * @Column(name="id")
     * @GeneratedValue()
     */
    private $itemId;

    /**
     * @ShardKey
     *
     * @var int
     */
    private $sharding;

    /**
     * @NaturalId("uk_item")
     *
     * @var string|null
     */
    private $itemNo;

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getSharding(): int
    {
        return $this->sharding;
    }

    public function setSharding(int $sharding): void
    {
        $this->sharding = $sharding;
    }

    public function getItemNo(): ?string
    {
        return $this->itemNo;
    }

    public function setItemNo(?string $itemNo): void
    {
        $this->itemNo = $itemNo;
    }
}
