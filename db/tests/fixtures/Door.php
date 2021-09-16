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

use kuiper\db\annotation\Id;

class Door
{
    /**
     * @var DoorId
     * @Id()
     */
    private $doorId;

    /**
     * @var string
     */
    private $name;

    /**
     * Door constructor.
     */
    public function __construct(DoorId $doorId)
    {
        $this->doorId = $doorId;
    }

    public function getDoorId(): DoorId
    {
        return $this->doorId;
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
