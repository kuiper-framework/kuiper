<?php

namespace kuiper\boot\providers;

use GuzzleHttp\Client as HttpClient;
use kuiper\annotations\DocReaderInterface;
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
use ProxyManager\Factory\RemoteObjectFactory;
use Symfony\Component\EventDispatcher\GenericEvent as Event;

class RpcClientProvider extends Provider
{
    public function register()
    {
        $rpcServices = [];
        foreach ($this->settings['app.rpc.services'] as $service) {
            $rpcServices[$service] = di\factory([$this, 'createProxy'], $service);
        }

        $this->services->addDefinitions(array_merge($rpcServices, [
            NormalizerInterface::class => di\get(Serializer::class),
        ]));
    }

    public function createProxy($serviceName)
    {
        $config = $this->settings['app.rpc'];
        $server = Arrays::fetch($config['servers'], $serviceName, $config['servers']['default']);
        if (empty($server)) {
            throw new \InvalidArgumentException("Server uri for '$serviceName' should not be empty");
        }
        if (parse_url($server, PHP_URL_SCHEME) == 'tcp') {
            $handler = new TcpHandler([$server]);
        } else {
            $options = array_merge($this->settings['app.http_client'], [
                'base_uri' => $server,
            ]);
            $handler = new HttpHandler(new HttpClient($options));
        }

        $client = new RpcClient($handler);
        $client->add(new Normalize($this->app->get(NormalizerInterface::class), $this->app->get(DocReaderInterface::class)), 'before:start', 'normalize');
        $client->add(new JsonRpc(Arrays::fetch($config, 'aliases', [])), 'before:call', 'jsonrpc');
        if (!empty($config['middlewares'])) {
            foreach ($config['middlewares'] as $middleware) {
                $middleware = (array) $middleware;
                $server->add(
                    $container->get($middleware[0]),
                    $position = isset($middleware[1]) ? $middleware[1] : 'before:call',
                    $id = isset($middleware[2]) ? $middleware[2] : null
                );
            }
        }
        $this->app->getEventDispatcher()->dispatch(Events::BOOT_RPC_CLIENT, new Event($client));

        $proxyConfig = new \ProxyManager\Configuration();
        $proxyConfig->setProxiesTargetDir($this->settings['app.runtime_path']);

        $factory = new RemoteObjectFactory($client, $proxyConfig);

        return $factory->createProxy($serviceName);
    }
}
