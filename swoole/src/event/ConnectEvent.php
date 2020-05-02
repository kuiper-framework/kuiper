<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class ConnectEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $clientId;

    /**
     * @var int
     */
    private $reactorId;

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getReactorId(): int
    {
        return $this->reactorId;
    }

    public function setReactorId(int $reactorId): void
    {
        $this->reactorId = $reactorId;
    }
}
