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

use kuiper\db\annotation\Convert;
use kuiper\db\annotation\GeneratedValue;
use kuiper\db\annotation\Id;
use kuiper\db\converter\DateConverter;

class User
{
    /**
     * @var int
     * @Id()
     * @GeneratedValue()
     */
    private $userId;

    /**
     * @var \DateTime
     * @Convert(DateConverter::class)
     */
    private $dob;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getDob(): \DateTime
    {
        return $this->dob;
    }

    public function setDob(\DateTime $dob): void
    {
        $this->dob = $dob;
    }
}
