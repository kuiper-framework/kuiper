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

use kuiper\db\attribute\Convert;
use kuiper\db\attribute\GeneratedValue;
use kuiper\db\attribute\Id;
use kuiper\db\converter\DateConverter;

class User
{
    #[Id]
    #[GeneratedValue]
    private ?int $userId = null;

    #[Convert(DateConverter::class)]
    private ?\DateTimeInterface $dob = null;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getDob(): \DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(\DateTimeInterface $dob): void
    {
        $this->dob = $dob;
    }
}
