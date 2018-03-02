<?php

namespace kuiper\boot\providers;

use GuzzleHttp\Client as HttpClient;
use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use kuiper\helper\Arrays;
use kuiper\rpc\client\Client as RpcClient;
use kuiper\rpc\client\HttpHandler;
use kuiper\rpc\client\middleware\JsonRpc;
use kuiper\rpc\client\middleware\Normalize;
use kuiper\rpc\client\TcpHandler;
use kuiper\serializer\NormalizerInterface;
use kuiper\serializer\Serializer;
use ProxyManager\Configuration;
use ProxyManager\Factory\RemoteObjectFactory;
use Symfony\Component\EventDispatcher\GenericEvent as Event;

/**
 * Provides rpc proxy client.
 *
 * add entry "rpc" in config/app.php
 *
 * "rpc" => [
 *     "gateway" => <gateway_uri>,
 *     "source" => <source_name>,
 *     "defaults" => [
 *         "timeout" => 5,
 *     ],
 *     "providers" => [
 *         <provider-name> => [
 *             "timeout" => 5,
 *             "server" => "{app.rpc.gateway}/<endpoint>",
 *             "services" => [
 *             ]
 *         ]
 *     ]
 * ]
 *
 * Class RpcClientProvider
 */
class RpcClientProvider extends Provider
{
    public function register()
    {
        $rpcServices = [];
        $config = $this->settings['app.rpc'];
        if (isset($config['services'])) {
            foreach ($config['services'] as $group => $services) {
                if (is_string($services)) {
                    $serviceName = $services;
                    $options = [
                        'server' => $this->getServer($serviceName, null),
                    ];
                    $rpcServices[$serviceName] = di\factory([$this, 'createProxy'], $serviceName, $options);
                } else {
                    foreach ($services as $serviceName) {
                        $options = [
                            'server' => $this->getServer($serviceName, $group),
                        ];
                        $rpcServices[$serviceName] = di\factory([$this, 'createProxy'], $serviceName, $options);
                    }
                }
            }
        }
        if (isset($config['providers'])) {
            foreach ($config['providers'] as $name => $provider) {
                if (empty($provider['services'])) {
                    error_log("Rpc provider '$name' missing services");
                    continue;
                }
                foreach ($provider['services'] as $serviceName) {
                    $rpcServices[$serviceName] = di\factory([$this, 'createProxy'], $serviceName, $provider);
                }
            }
        }

        $this->services->addDefinitions(array_merge($rpcServices, [
            NormalizerInterface::class => di\get(Serializer::class),
        ]));
    }

    public function createProxy($serviceName, array $options)
    {
        if (empty($options['server'])) {
            throw new \InvalidArgumentException("Server uri for '$serviceName' should not be empty");
        }
        if (parse_url($options['server'], PHP_URL_SCHEME) == 'tcp') {
            $handler = new TcpHandler([$options['server']]);
        } else {
            $httpOptions = array_merge(
                ['timeout' => 10, 'connect_timeout' => 3],
                Arrays::select($this->settings['app.rpc.defaults'] ?: [], ['timeout', 'connect_timeout']),
                Arrays::select($options, ['timeout', 'connect_timeout'])
            );
            $handler = new HttpHandler(new HttpClient($httpOptions), $this->prepareEndpoint($options['server']));
        }

        $client = new RpcClient($handler);
        $client->add($this->app->get(Normalize::class), 'before:start', 'normalize');
        $client->add(new JsonRpc(Arrays::fetch($options, 'aliases', [])), 'before:call', 'jsonrpc');
        if (!empty($this->settings['app.rpc.middlewares'])) {
            foreach ($this->settings['app.rpc.middlewares'] as $middleware) {
                $middleware = (array) $middleware;
                $client->add(
                    $this->app->get($middleware[0]),
                    $position = isset($middleware[1]) ? $middleware[1] : 'before:call',
                    $id = isset($middleware[2]) ? $middleware[2] : null
                );
            }
        }
        $this->app->getEventDispatcher()->dispatch(Events::BOOT_RPC_CLIENT, new Event($client));

        $proxyConfig = new Configuration();
        $proxyConfig->setProxiesTargetDir($this->settings['app.runtime_path']);

        $factory = new RemoteObjectFactory($client, $proxyConfig);

        return $factory->createProxy($serviceName);
    }

    /**
     * @param string $serviceName
     * @param string $group
     *
     * @return string
     */
    private function getServer($serviceName, $group)
    {
        $config = $this->settings['app.rpc.servers'];
        if (isset($config[$serviceName])) {
            return $config[$serviceName];
        }
        if (isset($group) && isset($config[$group])) {
            return $config[$group];
        }

        return $config['default'];
    }

    private function prepareEndpoint($uri)
    {
        return $uri.(strpos($uri, '?') === false ? '?' : '&').http_build_query(array_filter([
            'source' => $this->settings['app.rpc.source'],
            'host' => gethostname(),
        ]));
    }
}
