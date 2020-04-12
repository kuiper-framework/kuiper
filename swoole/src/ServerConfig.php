<?php

declare(strict_types=1);

namespace kuiper\swoole;

use Webmozart\Assert\Assert;

class ServerConfig
{
    /**
     * @var string
     */
    private $serverName;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var ServerPort[]
     */
    private $ports;

    /**
     * @var string
     */
    private $masterPidFile;

    /**
     * @var string
     */
    private $managerPidFile;

    /**
     * ServerConfig constructor.
     *
     * @param ServerPort[] $ports
     */
    public function __construct(string $serverName, array $settings, array $ports)
    {
        $this->serverName = $serverName;
        $this->settings = $settings;
        Assert::notEmpty($ports, 'at least one server port should be set');
        $this->ports = $ports;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return ServerPort[]
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    public function getPort(): ServerPort
    {
        return $this->ports[0];
    }

    public function getTaskWorkerNum(): int
    {
        return $this->settings[SwooleSetting::TASK_WORKER_NUM] ?? 0;
    }

    public function getWorkerNum(): int
    {
        return $this->settings[SwooleSetting::WORKER_NUM] ?? 0;
    }

    public function getTotalWorkerNum(): int
    {
        return $this->getTaskWorkerNum() + $this->getWorkerNum();
    }

    public function getMasterPidFile(): ?string
    {
        return $this->masterPidFile;
    }

    public function setMasterPidFile(string $masterPidFile): self
    {
        $this->masterPidFile = $masterPidFile;

        return $this;
    }

    public function getManagerPidFile(): ?string
    {
        return $this->managerPidFile;
    }

    public function setManagerPidFile(string $managerPidFile): self
    {
        $this->managerPidFile = $managerPidFile;

        return $this;
    }
}
