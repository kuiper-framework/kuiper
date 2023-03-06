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

namespace kuiper\tars;

use kuiper\helper\Properties;
use kuiper\swoole\Application;
use kuiper\tars\client\TarsProxyFactory;
use kuiper\tars\integration\ConfigServant;
use kuiper\tars\server\Config;

class TarsApplication extends Application
{
    /**
     * {@inheritDoc}
     */
    protected function parseConfig(string $configFile): Properties
    {
        return Config::parseFile($configFile);
    }

    /**
     * {@inheritDoc}
     */
    protected function addDefaultConfig(): void
    {
        parent::addDefaultConfig();
        $config = $this->getConfig();
        $config->merge([
            'application' => [
                'env' => $config->getString('application.tars.server.env', 'prod'),
                'name' => $config->getString('application.tars.server.app', 'app')
                    .'.'.$config->getString('application.tars.server.server', 'server'),
                'base_path' => $config->get('application.tars.server.basepath'),
                'data_path' => $config->get('application.tars.server.datapath'),
                'server' => [
                    'enable_php_server' => $config->getBool('application.tars.server.enable_php_server', false),
                ],
                'client' => [
                    'enable_dns' => $config->getBool('application.tars.client.enable_dns'),
                ],
                'logging' => [
                    'loggers' => [
                        'root' => [
                            'console' => $config->getBool('application.tars.server.enable_console_logging', true),
                            'level' => $config->getString('application.tars.server.logLevel', 'info'),
                        ],
                    ],
                    'path' => sprintf('%s/%s/%s',
                        $config->get('application.tars.server.logpath'),
                        $config->get('application.tars.server.app'),
                        $config->get('application.tars.server.server')),
                    'level' => [
                        'kuiper\\tars' => 'info',
                        'kuiper\\event' => 'info',
                    ],
                ],
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadEnv(array $envFiles = []): void
    {
        parent::loadEnv($this->downloadEnvFile());
    }

    private function downloadEnvFile(): array
    {
        $config = $this->getConfig();
        $env = $config->getString('application.tars.server.env_file');
        $app = $config->getString('application.tars.server.app');
        $server = $config->getString('application.tars.server.server');
        $locator = $config->getString('application.tars.client.locator');
        if ('' !== $env && '' !== $locator) {
            $proxyFactory = TarsProxyFactory::createDefault($locator);
            $localFile = $this->getBasePath().'/'.$env;
            /** @var ConfigServant $configServant */
            $configServant = $proxyFactory->create(ConfigServant::class);
            $ret = $configServant->loadConfig($app, $server, $env, $content);
            if (0 === $ret && !empty($content)) {
                file_put_contents($localFile, $content);
            }

            return [$env];
        }

        return [];
    }
}
