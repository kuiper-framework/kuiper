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

namespace kuiper\swoole;

use kuiper\helper\Properties;
use kuiper\swoole\constants\ServerSetting;
use Webmozart\Assert\Assert;

class ServerConfig
{
    /**
     * @var ServerPort[]
     */
    private readonly array $ports;

    /**
     * @var Properties
     */
    private readonly Properties $settings;

    private ?string $masterPidFile = null;

    private ?string $managerPidFile = null;

    /**
     * ServerConfig constructor.
     *
     * @param ServerPort[] $ports
     */
    public function __construct(private readonly string $serverName, array $ports)
    {
        Assert::notEmpty($ports, 'at least one server port should be set');
        usort($ports, static function (ServerPort $a, ServerPort $b) {
            if ($a->isHttpProtocol()) {
                return $b->isHttpProtocol() ? 0 : -1;
            }

            return $b->isHttpProtocol() ? 1 : 0;
        });
        $this->ports = array_values($ports);
        $this->settings = Properties::create($this->ports[0]->getSettings());
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getSettings(): Properties
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
        return $this->settings[ServerSetting::TASK_WORKER_NUM] ?? 0;
    }

    public function getWorkerNum(): int
    {
        return $this->settings[ServerSetting::WORKER_NUM] ?? 0;
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
