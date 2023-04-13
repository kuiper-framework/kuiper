<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware\tars;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\client\TarsRequest;
use kuiper\tars\core\Parameter;
use kuiper\tars\core\TarsMethod;
use kuiper\tars\type\VoidType;
use kuiper\tracing\Config;
use kuiper\tracing\Tracer;

use const OpenTracing\Formats\TEXT_MAP;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TraceClientRequestTest extends TestCase
{
    public function testName()
    {
        $options['ip'] = '127.0.0.1';
        $config = new Config($options, 'app', null, null);
        $config->setLogger(new NullLogger());
        Tracer::set([$config, 'createTracer']);

        $tracer = Tracer::get();
        $root = $tracer->extract(TEXT_MAP, [
            'jaeger-debug-id' => 'test',
        ]);
        $scope = $tracer->startActiveSpan('serve', [
            'child_of' => $root,
        ]);
        $span = $scope->getSpan();
        $middleware = new TraceClientRequest();
        $httpFactory = new HttpFactory();
        $method = new TarsMethod(
            'TestService',
            'app.server.TestObj',
            'test',
            [],
            [],
            new Parameter(
                0, '', false, VoidType::instance(), null
            )
        );
        $request = new TarsRequest(
            $httpFactory->createRequest('GET', '/'),
            $method,
            $httpFactory,
            1
        );
        $middleware->process($request, new class() implements RpcRequestHandlerInterface {
            public function handle(RpcRequestInterface $request): RpcResponseInterface
            {
                return new RpcResponse($request, new Response());
            }
        });
    }
}
