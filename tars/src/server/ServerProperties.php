<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use Symfony\Component\Validator\Constraints as Assert;
use wenbinye\tars\rpc\route\Endpoint;

class ServerProperties
{
    private const DEFAULT_SETTINGS = [
        ServerSetting::OPEN_LENGTH_CHECK => true,
        ServerSetting::PACKAGE_LENGTH_TYPE => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 0,
        ServerSetting::MAX_WAIT_TIME => 60,
        ServerSetting::RELOAD_ASYNC => true,
        ServerSetting::PACKAGE_MAX_LENGTH => 10485760,
    ];

    /**
     * The App namespace.
     *
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $app;

    /**
     * The server name.
     *
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $server;

    /**
     * The swoole server settings.
     *
     * @var array
     */
    private $serverSettings = [];

    /**
     * The basepath config value, equal to "$TARSPATH/tarsnode/data/$app.$server/bin".
     *
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $basePath;
    /**
     * The datapath config value, equal to "$TARSPATH/tarsnode/data/$app.$server/data".
     *
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $dataPath;
    /**
     * The logpath config value, equal to "$TARSPATH/app_log".
     *
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $logPath;
    /**
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $logLevel = 'DEBUG';
    /**
     * @var int
     */
    private $logSize = 15728640;  // 15M

    /**
     * @var Route|null
     */
    private $node;
    /**
     * @var Endpoint|null
     */
    private $local;

    /**
     * @var string|null
     */
    private $localIp;

    /**
     * @var string
     */
    private $logServantName = 'tars.tarslog.LogObj';
    /**
     * @var string
     */
    private $configServantName = 'tars.tarsconfig.ConfigObj';
    /**
     * @var string
     */
    private $notifyServantName = 'tars.tarsnotify.NotifyObj';

    /**
     * @var int|null
     */
    private $reloadInterval;
    /**
     * @var string|null
     */
    private $env;
    /**
     * @var string|null
     */
    private $emalloc;
    /**
     * @var string|null
     */
    private $startMode;
    /**
     * @var string|null
     */
    private $supervisorConfPath;
    /**
     * @var string
     */
    private $supervisorConfExtension = '.conf';
    /**
     * @var string|null
     */
    private $supervisorctl;
    /**
     * @Assert\Count(min=1)
     *
     * @var Adapter[]
     */
    private $adapters = [];

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
        return array_merge(self::DEFAULT_SETTINGS, $this->serverSettings);
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
     * @param string|int|null $reloadInterval
     */
    public function setReloadInterval($reloadInterval): void
    {
        if (isset($reloadInterval)) {
            $this->reloadInterval = (int) $reloadInterval;
        }
    }

    public function getNode(): ?Route
    {
        return $this->node;
    }

    public function setNode(?Route $node): void
    {
        $this->node = $node;
    }

    public function getLocal(): ?ServerAddress
    {
        return $this->local;
    }

    public function setLocal(?ServerAddress $local): void
    {
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

    public function getPortAdapters(): array
    {
        return $this->portAdapters;
    }

    public function setPortAdapters(array $portAdapters): void
    {
        $this->portAdapters = $portAdapters;
    }

    public function getServerName(): string
    {
        return $this->app.'.'.$this->server;
    }

    /**
     * @return mixed|null
     */
    public function getServerSetting(string $name)
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
            throw new \InvalidArgumentException("basepath '$basePath' does not exist");
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
            throw new \InvalidArgumentException("datapath '$dataPath' does not exist");
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
            throw new \InvalidArgumentException("logpath '$logPath' does not exist");
        }
        $this->logPath = rtrim(realpath($logPath), '/');
    }

    public function getAppLogPath(): string
    {
        return sprintf('%s/%s/%s', $this->logPath, $this->app, $this->server);
    }

    /**
     * @return AdapterProperties[]
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * @param AdapterProperties[] $adapters
     */
    public function setAdapters(array $adapters): void
    {
        usort($adapters, static function (AdapterProperties $a, AdapterProperties $b) {
            if ($b->getServerType() === $a->getServerType()) {
                return 0;
            }
            if (ServerType::fromValue($a->getServerType())->isHttpProtocol()) {
                return -1;
            }

            return 1;
        });
        $this->portAdapters = [];
        foreach ($adapters as $adapter) {
            $this->portAdapters[$adapter->getEndpoint()->getPort()][] = $adapter;
        }

        $this->adapters = $adapters;
    }

    public function hasAdapter(string $name): bool
    {
        return isset($this->adapters[$name]);
    }

    public function getAdapter(string $name): ?AdapterProperties
    {
        return $this->adapters[$name] ?? null;
    }

    public function getPrimaryAdapter(): AdapterProperties
    {
        return array_values($this->adapters)[0];
    }

    /**
     * @return AdapterProperties[]
     */
    public function getAdaptersByPort(int $port): array
    {
        return $this->portAdapters[$port] ?? [];
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
