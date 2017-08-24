<?php

require __DIR__.'/vendor/autoload.php';

use GuzzleHttp\Client as HttpClient;
use kuiper\rpc\client\Client;
use kuiper\rpc\client\HttpHandler;
use kuiper\rpc\client\middleware\JsonRpc;
use ProxyManager\Factory\RemoteObjectFactory;

$client = new Client(new HttpHandler(new HttpClient([
    'base_uri' => 'http://localhost:9527',
])));
$client->add(new JsonRpc());
$factory = new RemoteObjectFactory($client);
$calc = $factory->createProxy(CalculatorInterface::class);

echo '1 + 2 = '.$calc->add(1, 2), "\n";
