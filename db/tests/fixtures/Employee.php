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

use kuiper\db\attribute\CreationTimestamp;
use kuiper\db\attribute\GeneratedValue;
use kuiper\db\attribute\Id;
use kuiper\db\attribute\ShardKey;
use kuiper\db\attribute\UpdateTimestamp;

class Employee
{
    #[Id]
    #[GeneratedValue]
    private ?int $id = null;

    #[CreationTimestamp]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimestamp]
    private ?\DateTimeInterface $updateTime = null;

    #[ShardKey]
    private ?int $sharding = null;

    private ?string $name = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCreateTime(): \DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): \DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getSharding(): int
    {
        return $this->sharding;
    }

    public function setSharding(int $sharding): void
    {
        $this->sharding = $sharding;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
