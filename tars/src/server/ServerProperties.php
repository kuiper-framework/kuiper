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

namespace kuiper\tars\server;

use InvalidArgumentException;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\transporter\Endpoint;
use kuiper\serializer\attribute\SerializeName;
use kuiper\swoole\constants\ServerType;
use kuiper\tars\core\EndpointParser;
use Symfony\Component\Validator\Constraints as Assert;

class ServerProperties
{
    /**
     * The App namespace.
     */
    #[Assert\NotBlank]
    private ?string $app = null;

    /**
     * The server name.
     */
    #[Assert\NotBlank]
    private ?string $server = null;

    /**
     * The swoole server settings.
     */
    private array $serverSettings = [];

    /**
     * The basepath config value, equal to "$TARSPATH/tarsnode/data/$app.$server/bin".
     */
    #[Assert\NotBlank]
    #[SerializeName("basepath")]
    private ?string $basePath = null;

    /**
     * The datapath config value, equal to "$TARSPATH/tarsnode/data/$app.$server/data".
     */
    #[Assert\NotBlank]
    #[SerializeName("datapath")]
    private ?string $dataPath = null;

    /**
     * The logpath config value, equal to "$TARSPATH/app_log".
     */
    #[Assert\NotBlank]
    #[SerializeName("logpath")]
    private ?string $logPath = null;

    #[Assert\NotBlank]
    private string $logLevel = 'DEBUG';

    private int $logSize = 15728640;  // 15M

    private ?ServiceEndpoint $node = null;

    private ?Endpoint $local = null;

    #[SerializeName("localip")]
    private ?string $localIp = null;

    private string $logServantName = 'tars.tarslog.LogObj';

    private string $configServantName = 'tars.tarsconfig.ConfigObj';

    private string $notifyServantName = 'tars.tarsnotify.NotifyObj';

    private bool $daemonize = false;

    private ?int $reloadInterval = null;

    private ?string $env = null;

    private ?string $emalloc = null;

    private ?string $startMode = null;

    private ?string $supervisorConfPath = null;

    private string $supervisorConfExtension = '.conf';

    private ?string $supervisorctl = null;

    private ?string $php = null;

    /**
     * @var Adapter[]
     */
    #[Assert\Count(min: 1)]
    private array $adapters = [];

    public function getApp(): ?string
    {
        return $this->app;
    }

    public function setApp(?string $app): void
    {
        $this->app = $app;
    }

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function setServer(?string $server): void
    {
        $this->server = $server;
    }

    public function getServerSettings(): array
    {
        return $this->serverSettings;
    }

    public function setServerSettings(array $serverSettings): void
    {
        $this->serverSettings = $serverSettings;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function setLogLevel(?string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    public function getLogSize(): int
    {
        return $this->logSize;
    }

    public function setLogSize(int $logSize): void
    {
        $this->logSize = $logSize;
    }

    public function getReloadInterval(): ?int
    {
        return $this->reloadInterval;
    }

    /**
     * @param int|string|null $reloadInterval
     */
    public function setReloadInterval(int|string|null $reloadInterval): void
    {
        if (isset($reloadInterval)) {
            $this->reloadInterval = (int) $reloadInterval;
        }
    }

    public function getNode(): ?ServiceEndpoint
    {
        return $this->node;
    }

    /**
     * @param string|ServiceEndpoint $node
     */
    public function setNode(ServiceEndpoint|string $node): void
    {
        if (is_string($node)) {
            $node = EndpointParser::parseServiceEndpoint($node);
        }
        $this->node = $node;
    }

    public function getLocal(): ?Endpoint
    {
        return $this->local;
    }

    /**
     * @param string|Endpoint $local
     */
    public function setLocal(Endpoint|string $local): void
    {
        if (is_string($local)) {
            $local = EndpointParser::parse($local);
        }
        $this->local = $local;
    }

    public function getLocalIp(): ?string
    {
        return $this->localIp;
    }

    public function setLocalIp(?string $localIp): void
    {
        $this->localIp = $localIp;
    }

    public function getLogServantName(): string
    {
        return $this->logServantName;
    }

    public function setLogServantName(string $logServantName): void
    {
        $this->logServantName = $logServantName;
    }

    public function getConfigServantName(): string
    {
        return $this->configServantName;
    }

    public function setConfigServantName(string $configServantName): void
    {
        $this->configServantName = $configServantName;
    }

    public function getNotifyServantName(): string
    {
        return $this->notifyServantName;
    }

    public function setNotifyServantName(string $notifyServantName): void
    {
        $this->notifyServantName = $notifyServantName;
    }

    /**
     * @return bool
     */
    public function isDaemonize(): bool
    {
        return $this->daemonize;
    }

    /**
     * @param bool $daemonize
     */
    public function setDaemonize(bool $daemonize): void
    {
        $this->daemonize = $daemonize;
        $this->serverSettings['daemonize'] = $daemonize;
    }

    public function getEnv(): ?string
    {
        return $this->env;
    }

    public function setEnv(?string $env): ServerProperties
    {
        $this->env = $env;

        return $this;
    }

    public function getEmalloc(): ?string
    {
        return $this->emalloc;
    }

    public function setEmalloc(?string $emalloc): ServerProperties
    {
        $this->emalloc = $emalloc;

        return $this;
    }

    public function getStartMode(): ?string
    {
        return $this->startMode;
    }

    public function setStartMode(?string $startMode): void
    {
        $this->startMode = $startMode;
    }

    public function getSupervisorConfPath(): ?string
    {
        return $this->supervisorConfPath;
    }

    public function setSupervisorConfPath(?string $supervisorConfPath): void
    {
        $this->supervisorConfPath = $supervisorConfPath;
    }

    public function getSupervisorctl(): ?string
    {
        return $this->supervisorctl;
    }

    public function setSupervisorctl(?string $supervisorctl): void
    {
        $this->supervisorctl = $supervisorctl;
    }

    public function getSupervisorConfExtension(): string
    {
        return $this->supervisorConfExtension;
    }

    public function setSupervisorConfExtension(string $supervisorConfExtension): void
    {
        $this->supervisorConfExtension = $supervisorConfExtension;
    }

    public function getServerName(): string
    {
        return $this->app.'.'.$this->server;
    }

    public function getServerSetting(string $name): mixed
    {
        return $this->serverSettings[$name] ?? null;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setBasePath(string $basePath): void
    {
        if (!is_dir($basePath)) {
            throw new InvalidArgumentException("basepath '$basePath' does not exist");
        }
        $this->basePath = rtrim(realpath($basePath), '/');
    }

    public function getSourcePath(): string
    {
        return $this->basePath.'/src';
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
    }

    public function setDataPath(string $dataPath): void
    {
        if (!is_dir($dataPath) && !mkdir($dataPath) && !is_dir($dataPath)) {
            throw new InvalidArgumentException("datapath '$dataPath' does not exist");
        }
        $this->dataPath = rtrim(realpath($dataPath), '/');
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function setLogPath(string $logPath): void
    {
        if (!is_dir($logPath) && !mkdir($logPath) && !is_dir($logPath)) {
            throw new InvalidArgumentException("logpath '$logPath' does not exist");
        }
        $this->logPath = rtrim(realpath($logPath), '/');
    }

    /**
     * @return string|null
     */
    public function getPhp(): ?string
    {
        return $this->php;
    }

    /**
     * @param string|null $php
     */
    public function setPhp(?string $php): void
    {
        $this->php = $php;
    }

    public function getAppLogPath(): string
    {
        return sprintf('%s/%s/%s', $this->logPath, $this->app, $this->server);
    }

    /**
     * @return Adapter[]
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * @param Adapter[] $adapters
     */
    public function setAdapters(array $adapters): void
    {
        usort($adapters, static function (Adapter $a, Adapter $b) {
            if ($b->getServerType() === $a->getServerType()) {
                return 0;
            }
            if ($a->getServerType()->isHttpProtocol()) {
                return -1;
            }

            return 1;
        });

        $this->adapters = $adapters;
    }

    public function hasAdapter(string $name): bool
    {
        return isset($this->adapters[$name]);
    }

    public function getAdapter(string $name): ?Adapter
    {
        return $this->adapters[$name] ?? null;
    }

    public function getPrimaryAdapter(): Adapter
    {
        return array_values($this->adapters)[0];
    }

    /**
     * @return Adapter[]
     */
    public function getAdaptersByPort(int $port): array
    {
        $adapters = [];
        foreach ($this->adapters as $adapter) {
            if ($adapter->getEndpoint()->getPort() === $port) {
                $adapters[] = $adapter;
            }
        }

        return $adapters;
    }

    public function getMasterPidFile(): string
    {
        return $this->dataPath.'/master.pid';
    }

    public function getManagerPidFile(): string
    {
        return $this->dataPath.'/manager.pid';
    }

    public function getServerPidFile(): string
    {
        return $this->dataPath.'/'.$this->getServerName().'.pid';
    }

    public function isExternalMode(): bool
    {
        return 'external' === $this->startMode;
    }
}
