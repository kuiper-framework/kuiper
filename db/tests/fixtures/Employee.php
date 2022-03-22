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

use kuiper\db\annotation\CreationTimestamp;
use kuiper\db\annotation\GeneratedValue;
use kuiper\db\annotation\Id;
use kuiper\db\annotation\ShardKey;
use kuiper\db\annotation\UpdateTimestamp;

class Employee
{
    /**
     * @Id()
     * @GeneratedValue()
     *
     * @var int
     */
    private $id;

    /**
     * @CreationTimestamp()
     *
     * @var \DateTimeInterface
     */
    private $createTime;

    /**
     * @UpdateTimestamp()
     *
     * @var \DateTimeInterface
     */
    private $updateTime;
    /**
     * @var int
     * @ShardKey()
     */
    private $sharding;
    /**
     * @var string
     */
    private $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCreateTime(): \DateTime
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTime $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function getUpdateTime(): \DateTime
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTime $updateTime): void
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
