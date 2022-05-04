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
use kuiper\db\attribute\UpdateTimestamp;

class Department
{
    #[Id]
    #[GeneratedValue]
    private ?int $id = null;

    #[CreationTimestamp]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimestamp]
    private ?\DateTimeInterface $updateTime = null;

    private ?string $name = null;

    private ?string $departNo = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Department
    {
        $this->id = $id;

        return $this;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): Department
    {
        $this->createTime = $createTime;

        return $this;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTimeInterface $updateTime): Department
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Department
    {
        $this->name = $name;

        return $this;
    }

    public function getDepartNo(): string
    {
        return $this->departNo;
    }

    public function setDepartNo(string $departNo): void
    {
        $this->departNo = $departNo;
    }
}
