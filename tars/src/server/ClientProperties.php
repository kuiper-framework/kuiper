<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\tars\core\EndpointParser;
use Symfony\Component\Validator\Constraints as Assert;

class ClientProperties
{
    /**
     * @var int
     */
    private $asyncThread = 3;
    /**
     * @var ServiceEndpoint|null
     */
    private $locator;
    /**
     * @var int
     */
    private $syncInvokeTimeout = 20000;
    /**
     * @var int
     */
    private $asyncInvokeTimeout = 20000;
    /**
     * @var int
     */
    private $refreshEndpointInterval = 60000;
    /**
     * @Assert\Range(min=1000)
     *
     * @var int
     */
    private $keepAliveInterval = 20000;
    /**
     * @Assert\Range(min=1000)
     *
     * @var int
     */
    private $reportInterval = 60000;
    /**
     * @var string
     */
    private $statServantName = 'tars.tarsstat.StatObj';
    /**
     * @var string
     */
    private $propertyServantName = 'tars.tarsproperty.PropertyObj';
    /**
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $moduleName;
    /**
     * @var int
     */
    private $sampleRate = 100000;
    /**
     * @var int
     */
    private $maxSampleCount = 50;

    public function getAsyncThread(): int
    {
        return $this->asyncThread;
    }

    public function setAsyncThread(int $asyncThread): void
    {
        $this->asyncThread = $asyncThread;
    }

    public function getLocator(): ?ServiceEndpoint
    {
        return $this->locator;
    }

    /**
     * @param string|ServiceEndpoint $locator
     */
    public function setLocator($locator): void
    {
        if (is_string($locator)) {
            $locator = EndpointParser::parseServiceEndpoint($locator);
        }
        $this->locator = $locator;
    }

    public function getSyncInvokeTimeout(): int
    {
        return $this->syncInvokeTimeout;
    }

    public function setSyncInvokeTimeout(int $syncInvokeTimeout): void
    {
        $this->syncInvokeTimeout = $syncInvokeTimeout;
    }

    public function getAsyncInvokeTimeout(): int
    {
        return $this->asyncInvokeTimeout;
    }

    public function setAsyncInvokeTimeout(int $asyncInvokeTimeout): void
    {
        $this->asyncInvokeTimeout = $asyncInvokeTimeout;
    }

    public function getRefreshEndpointInterval(): int
    {
        return $this->refreshEndpointInterval;
    }

    public function setRefreshEndpointInterval(int $refreshEndpointInterval): void
    {
        $this->refreshEndpointInterval = $refreshEndpointInterval;
    }

    public function getKeepAliveInterval(): int
    {
        return $this->keepAliveInterval;
    }

    public function setKeepAliveInterval(int $keepAliveInterval): void
    {
        $this->keepAliveInterval = $keepAliveInterval;
    }

    public function getReportInterval(): int
    {
        return $this->reportInterval;
    }

    public function setReportInterval(int $reportInterval): void
    {
        $this->reportInterval = $reportInterval;
    }

    public function getStatServantName(): string
    {
        return $this->statServantName;
    }

    public function setStatServantName(string $statServantName): void
    {
        $this->statServantName = $statServantName;
    }

    public function getPropertyServantName(): string
    {
        return $this->propertyServantName;
    }

    public function setPropertyServantName(string $propertyServantName): void
    {
        $this->propertyServantName = $propertyServantName;
    }

    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    public function setModuleName(?string $moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    public function setSampleRate(int $sampleRate): void
    {
        $this->sampleRate = $sampleRate;
    }

    public function getMaxSampleCount(): int
    {
        return $this->maxSampleCount;
    }

    public function setMaxSampleCount(int $maxSampleCount): void
    {
        $this->maxSampleCount = $maxSampleCount;
    }
}
