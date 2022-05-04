<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\attribute\Column;
use kuiper\db\attribute\GeneratedValue;
use kuiper\db\attribute\Id;
use kuiper\db\attribute\NaturalId;
use kuiper\db\attribute\ShardKey;

class Item
{
    #[Id]
    #[GeneratedValue]
    #[Column("id")]
    private ?int $itemId = null;

    #[ShardKey]
    private ?int $sharding = null;

    #[NaturalId("uk_item")]
    private ?string $itemNo = null;

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
