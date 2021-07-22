<?php

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

    /**
     * @return string[]
     */
    public function getStatus(): array
    {
        return $this->packet->status;
    }
}
