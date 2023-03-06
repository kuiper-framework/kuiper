<?php

declare(strict_types=1);

namespace kuiper\tracing\web;

use kuiper\tracing\Config;
use kuiper\tracing\Constants;
use kuiper\tracing\Tracer;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class TracingRequestTest extends TestCase
{
    public function testNoDebugId()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $config = new Config(
            [
                'enabled' => false,
                'sampler' => [
                    'type' => 'const',
                    'param' => 1,
                ],
            ],
            'test',
            $logger
        );
        Tracer::set(function () use ($config) {
            return $config->createTracer();
        });
        $tracingRequest = new TraceWebRequest($config);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new Response());
        $request = new ServerRequest();
        $tracingRequest->process($request, $handler);
        $this->assertEmpty($logHandler->getRecords());
        // var_export($logHandler->getRecords());
    }

    public function testDebugId()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('test', [$logHandler]);
        $config = new Config(
            [
                'enabled' => false,
                'logging' => true,
                'sampler' => [
                    'type' => 'const',
                    'param' => 1,
                ],
            ],
            'test',
            $logger
        );
        Tracer::set(function () use ($config) {
            return $config->createTracer();
        });
        $tracingRequest = new TraceWebRequest($config);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->willReturn(new Response());
        $request = new ServerRequest();
        $request = $request->withHeader(Constants::DEBUG_ID_HEADER_KEY, '1');
        $tracingRequest->process($request, $handler);
        $this->assertCount(1, $logHandler->getRecords());
    }
}
