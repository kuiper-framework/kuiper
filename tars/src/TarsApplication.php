<?php

declare(strict_types=1);

namespace kuiper\tars;

use kuiper\swoole\Application;
use kuiper\tars\client\TarsProxyFactory;
use kuiper\tars\integration\ConfigServant;

class TarsApplication extends Application
{
    protected function downloadEnvFile(): array
    {
        $config = $this->getConfig();
        $env = $config->getString('application.tars.server.env_file');
        $app = $config->getString('application.tars.server.app');
        $server = $config->getString('application.tars.server.server');
        $locator = $config->getString('application.tars.client.locator');
        if ('' !== $env && '' !== $locator) {
            $proxyFactory = new TarsProxyFactory();
            $proxyFactory->setRegistryServiceEndpoint($locator);
            $localFile = $this->getBasePath().'/'.$env;
            /** @var ConfigServant $configServant */
            $configServant = $proxyFactory->create(ConfigServant::class);
            $ret = $configServant->loadConfig($app, $server, $env, $content);
            if (0 === $ret && !empty($content)) {
                file_put_contents($localFile, $content);
            }

            return [$localFile];
        }

        return [];
    }

    protected function loadEnv(array $envFiles = []): void
    {
        parent::loadEnv($this->downloadEnvFile());
    }

    protected function addDefaultConfig(): void
    {
        parent::addDefaultConfig();
        $config = $this->getConfig();
        $config->merge([
            'application' => [
                'env' => $config->getString('application.tars.server.env', 'prod'),
                'name' => $config->getString('application.tars.server.server'),
                'base-path' => $config->getString('application.tars.server.basepath'),
                'data-path' => $config->getString('application.tars.server.datapath'),
                'server' => [
                    'enable-php-server' => $config->getBool('application.tars.server.enable_php_server', false),
                ],
                'listeners' => [
//                    StartEventListener::class,
//                    ReloadWorkerListener::class,
//                    ManagerStartEventListener::class,
//                    WorkerStartEventListener::class,
//                    TaskEventListener::class,
//                    ReopenLogFile::class,
//                    WorkerKeepAlive::class,
                ],
                'tars' => [
                    'middleware' => [
                        'client' => [
//                            RequestLog::class,
//                            ErrorHandler::class,
//                            AddRequestReferer::class,
//                            SendStat::class,
//                            Retry::class,
                        ],
                        'servant' => [
//                            ServerRequestLog::class,
                        ],
                    ],
                    'collectors' => [
//                        ServiceMemoryCollector::class,
//                        WorkerNumCollector::class,
                    ],
                ],
                'logging' => [
                    'path' => $config->getString('application.tars.server.logpath'),
                    'level' => [
                        'kuiper\\tars' => 'info',
                    ],
                ],
            ],
        ]);
    }
}
