<?php

declare(strict_types=1);

namespace kuiper\db\fixtures;

use kuiper\db\annotation\CreationTimestamp;
use kuiper\db\annotation\UpdateTimestamp;

class Department
{
    /**
     * @var int
     */
    private $id;

    /**
     * @CreationTimestamp()
     *
     * @var \DateTime
     */
    private $createTime;

    /**
     * @UpdateTimestamp()
     *
     * @var \DateTime
     */
    private $updateTime;

    /**
     * @var string
     */
    private $name;

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
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTime $createTime): Department
    {
        $this->createTime = $createTime;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTime $updateTime): Department
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
}
