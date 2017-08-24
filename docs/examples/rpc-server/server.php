<?php

require __DIR__.'/vendor/autoload.php';

use kuiper\rpc\Request;
use kuiper\rpc\Response;
use kuiper\rpc\server\middleware\JsonRpc;
use kuiper\rpc\server\Server;
use kuiper\rpc\server\ServiceResolver;

$resolver = new ServiceResolver();
$resolver->add(new Calculator(), CalculatorInterface::class);
$server = new Server($resolver);
$server->add(new JsonRpc());

$request = new Request(file_get_contents('php://input'));
$response = $server->serve($request, new Response());
echo $response->getBody(), "\n";
