<?php

declare(strict_types=1);

namespace kuiper\tars;

use Dotenv\Dotenv;
use kuiper\helper\Properties;
use kuiper\swoole\Application;
use kuiper\tars\config\TarsClientConfiguration;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\integration\ConfigServant;
use kuiper\tars\integration\QueryFServant;
use kuiper\tars\server\Config;

class TarsApplication extends Application
{
    /**
     * @throws exception\ConfigException
     */
    protected function loadConfig(): Properties
    {
        [$configFile, $properties] = $this->parseArgv();
        if (!is_readable($configFile)) {
            throw new \InvalidArgumentException("config file '$configFile' is not readable");
        }
        $config = Config::parseFile($configFile);
        $this->addDefaultConfig($config);
        $this->addCommandLineOptions($config, $properties);
        $this->loadEnvFile($config);
        /** @phpstan-ignore-next-line */
        $configFile = APP_PATH.'/src/config.php';
        if (file_exists($configFile)) {
            /* @noinspection PhpIncludeInspection */
            $config->merge(require $configFile);
        }
        $config->replacePlaceholder();

        return $config;
    }

    private function addCommandLineOptions(Properties $config, array $properties): void
    {
        foreach ($properties as $i => $value) {
            if (!strpos($value, 'application.')) {
                $value = 'application.'.$value;
                $properties[$i] = $value;
            }
        }
        $define = parse_ini_string(implode("\n", $properties));
        if (is_array($define)) {
            foreach ($define as $key => $value) {
                $config->set($key, $value ?? null);
            }
        }
    }

    private function addDefaultConfig(Properties $config): void
    {
        $config->merge([
            'application' => [
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

    protected function loadEnvFile(Properties $config): void
    {
        if (!class_exists(Dotenv::class)) {
            return;
        }
        $envFiles = ['.env'];
        $env = $config->getString('application.tars.server.env_file');
        $app = $config->getString('application.tars.server.app');
        $server = $config->getString('application.tars.server.server');
        $locator = $config->getString('application.tars.client.locator');
        if ('' !== $env && '' !== $locator) {
            $localFile = $this->getBasePath().'/'.$env;
            $clientFactory = new TarsClientConfiguration();
            /** @var QueryFServant $registry */
            $registry = $clientFactory->createClient(QueryFServant::class, [
                'endpoint' => EndpointParser::parseServiceEndpoint($locator)->getEndpoints()[0],
            ]);
            $endpoints = $registry->findObjectById('tars.tarsconfig.ConfigObj');

            /** @var ConfigServant $configServant */
            $configServant = $clientFactory->createClient(ConfigServant::class, [
                'endpoint' => EndpointParser::fromEndpointF($endpoints[0]),
            ]);
            $ret = $configServant->loadConfig($app, $server, $env, $content);
            if (0 === $ret && !empty($content)) {
                file_put_contents($localFile, $content);
            }
            if (is_readable($localFile)) {
                array_unshift($envFiles, $env);
            }
        }
        Dotenv::createImmutable($this->getBasePath(), $envFiles, false)
            ->safeLoad();
    }

    private function parseArgv(): array
    {
        $configFile = null;
        $properties = [];
        $commandName = null;
        $argv = $_SERVER['argv'];
        $rest = [];
        while (null !== $token = array_shift($argv)) {
            if ('--' === $token) {
                $rest[] = $token;
                break;
            }
            if (0 === strpos($token, '--')) {
                $name = substr($token, 2);
                $pos = strpos($name, '=');
                if (false !== $pos) {
                    $value = substr($name, $pos + 1);
                    $name = substr($name, 0, $pos);
                }
                if ('config' === $name) {
                    $configFile = $value ?? array_shift($argv);
                } elseif ('define' === $name) {
                    $properties[] = $value ?? array_shift($argv);
                } else {
                    $rest[] = $token;
                }
            } elseif ('-' === $token[0] && 2 === strlen($token) && 'D' === $token[1]) {
                $properties[] = array_shift($argv);
            } else {
                $rest[] = $token;
            }
        }
        $_SERVER['argv'] = array_merge($rest, $argv);

        return [$configFile, $properties];
    }
}
