<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use InvalidArgumentException;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\transporter\Endpoint;
use kuiper\serializer\annotation\SerializeName;
use kuiper\swoole\constants\ServerType;
use kuiper\tars\core\EndpointParser;
use Symfony\Component\Validator\Constraints as Assert;

class ServerProperties
{
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
     * @SerializeName("basepath")
     *
     * @var string|null
     */
    private $basePath;
    /**
     * The datapath config value, equal to "$TARSPATH/tarsnode/data/$app.$server/data".
     *
     * @Assert\NotBlank()
     * @SerializeName("datapath")
     *
     * @var string|null
     */
    private $dataPath;
    /**
     * The logpath config value, equal to "$TARSPATH/app_log".
     *
     * @Assert\NotBlank()
     * @SerializeName("logpath")
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
     * @var ServiceEndpoint|null
     */
    private $node;
    /**
     * @var Endpoint|null
     */
    private $local;

    /**
     * @SerializeName("localip")
     *
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
     * @var bool
     */
    private $daemonize;

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
     * @param string|int|null $reloadInterval
     */
    public function setReloadInterval($reloadInterval): void
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
    public function setNode($node): void
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
    public function setLocal($local): void
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
            if (ServerType::fromValue($a->getServerType())->isHttpProtocol()) {
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
