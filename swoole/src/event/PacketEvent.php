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

namespace kuiper\swoole\event;

class PacketEvent extends AbstractServerEvent
{
    /**
     * @var string
     */
    private $data;
    /**
     * @var array
     */
    private $clientInfo;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getClientInfo(): array
    {
        return $this->clientInfo;
    }

    public function setClientInfo(array $clientInfo): void
    {
        $this->clientInfo = $clientInfo;
    }
}
