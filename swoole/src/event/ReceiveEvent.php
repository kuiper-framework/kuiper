<?php

declare(strict_types=1);

namespace kuiper\swoole\event;

class ReceiveEvent extends AbstractServerEvent
{
    /**
     * @var int
     */
    private $clientId;

    /**
     * @var int
     */
    private $reactorId;
    /**
     * @var string
     */
    private $data;

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

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }
}
