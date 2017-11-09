<?php

namespace kuiper\boot\providers;

use kuiper\boot\Events;
use kuiper\boot\Provider;
use kuiper\di;
use kuiper\rpc\server\middleware\JsonRpc;
use kuiper\rpc\server\middleware\JsonRpcErrorHandler;
use kuiper\rpc\server\middleware\Normalize;
use kuiper\rpc\server\Server;
use kuiper\rpc\server\ServerInterface;
use kuiper\rpc\server\ServiceResolver;
use kuiper\rpc\server\ServiceResolverInterface;
use kuiper\rpc\server\util\HealthyCheckService;
use kuiper\rpc\server\util\HealthyCheckServiceInterface;
use kuiper\serializer\NormalizerInterface;
use kuiper\serializer\Serializer;
use Symfony\Component\EventDispatcher\GenericEvent as Event;

class RpcServerProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            ServiceResolverInterface::class => di\factory([$this, 'provideServiceResolver']),
            ServerInterface::class => di\factory([$this, 'provideRpcServer']),
            NormalizerInterface::class => di\get(Serializer::class),
            HealthyCheckServiceInterface::class => di\object(HealthyCheckService::class),
        ]);
    }

    public function provideServiceResolver()
    {
        $resovler = new ServiceResolver();
        $resovler->setContainer($this->app->getContainer());
        $resovler->add(HealthyCheckServiceInterface::class);
        foreach ($this->settings['app.rpc_server.services'] as $service) {
            $resovler->add($service);
        }

        return $resovler;
    }

    public function provideRpcServer()
    {
        $container = $this->app->getContainer();
        $server = $container->get(Server::class);
        $server->setContainer($container);
        $server->add($container->get(JsonRpc::class), 'before:start', 'jsonrpc');
        $server->add($container->get(Normalize::class), 'before:call', 'normalize');
        $server->add($container->get(JsonRpcErrorHandler::class), 'before:normalize', 'error');

        $middlewares = $this->settings['app.rpc_server.middlewares'];
        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                $middleware = (array) $middleware;
                $server->add(
                    $container->get($middleware[0]),
                    $position = isset($middleware[1]) ? $middleware[1] : 'before:call',
                    $id = isset($middleware[2]) ? $middleware[2] : null
                );
            }
        }

        $this->app->getEventDispatcher()->dispatch(Events::BOOT_RPC_SERVER, new Event($server));

        return $server;
    }
}
