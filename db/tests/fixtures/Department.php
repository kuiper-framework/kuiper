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
use kuiper\db\annotation\UpdateTimestamp;

class Department
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
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $departNo;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Department
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeInterface $createTime): Department
    {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUpdateTime()
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
