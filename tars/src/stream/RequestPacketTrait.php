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

namespace kuiper\tars\stream;

trait RequestPacketTrait
{
    /**
     * @var RequestPacket
     */
    private $packet;

    public function getVersion(): int
    {
        return $this->packet->iVersion;
    }

    public function getPacketType(): int
    {
        return $this->packet->cPacketType;
    }

    public function getMessageType(): int
    {
        return $this->packet->iMessageType;
    }

    public function getRequestId(): int
    {
        return $this->packet->iRequestId;
    }

    public function getServantName(): string
    {
        return $this->packet->sServantName;
    }

    public function getFuncName(): string
    {
        return $this->packet->sFuncName;
    }

    public function getTimeout(): int
    {
        return $this->packet->iTimeout;
    }

    /**
     * @return string[]
     */
    public function getContext(): array
    {
        return $this->packet->context;
    }

    public function setContext(array $context): void
    {
        $this->packet->context = $context;
    }

    /**
     * @return string[]
     */
    public function getStatus(): array
    {
        return $this->packet->status;
    }

    public function setStatus(array $status): void
    {
        $this->packet->status = $status;
    }
}
