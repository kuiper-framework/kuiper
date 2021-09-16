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

namespace kuiper\web\middleware;

use Carbon\Carbon;
use GuzzleHttp\Psr7\BufferStream;
use kuiper\helper\Arrays;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\Test\TestLogger;

class AccessLogTest extends TestCase
{
    /**
     * @var AccessLog
     */
    private $accessLog;
    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @return array
     */
    protected function setUp(): void
    {
        $accessLog = new AccessLog();
        $logger = new TestLogger();
        $accessLog->setLogger($logger);
        $this->accessLog = $accessLog;
        $this->logger = $logger;
    }

    public function testProcess()
    {
        $this->accessLog->process($this->createRequest(), $this->createHandler());
        // var_export($this->logger->records);
//        $this->assertEquals($this->logger->records, [array(
//            'level' => 'info',
//            'message' => '127.0.0.1 -  [23/Jan/2021:11:37:04 +0800] "GET / HTTP/1.1" 200 0 "" "curl/7.64.1" "127.0.0.1" rt=1.90',
//            'context' =>
//                array(
//                    'query' => 'foo=1',
//                ),
//        )]);
        $this->assertCount(1, $this->logger->records);
    }

    public function testFilter()
    {
        $accessLog = new AccessLog(
            AccessLog::MAIN, [],
            0,
            '%d/%b/%Y:%H:%M:%S %z',
            function (ServerRequestInterface $request, $response) {
                return '/status.html' !== $request->getUri()->getPath();
            });
        $accessLog->setLogger($this->logger);
        $accessLog->process($this->createRequest(), $this->createHandler());
        // var_export($this->logger->records);
        $this->assertCount(0, $this->logger->records);
    }

    public function testBinaryBody()
    {
        Carbon::setTestNow('2020-01-01 00:01:00.30323');
        $accessLog = new AccessLog(
            AccessLog::MAIN, ['pid', 'body'],
            0,
            function () { return Carbon::now()->format('Y-m-d H:i:s.v'); }
        );
        $accessLog->setLogger($this->logger);
        $request = $this->createRequest();
        $body = new BufferStream();
        $body->write("\x01\x02");

        $accessLog->process($request->withBody($body), $this->createHandler());
        //var_export($this->logger->records);
        $this->assertEquals('body with 2 bytes', $this->logger->records[0]['context']['body']);
        $this->assertCount(1, $this->logger->records);
        Carbon::setTestNow();
    }

    public function testDateFormatter()
    {
        Carbon::setTestNow('2020-01-01 00:01:00.30323');
        $accessLog = new AccessLog(
            AccessLog::MAIN, ['pid'],
            0,
            function () { return Carbon::now()->format('Y-m-d H:i:s.v'); }
        );
        $accessLog->setLogger($this->logger);
        $accessLog->process($this->createRequest(), $this->createHandler());
        // var_export($this->logger->records);
        $this->assertCount(1, $this->logger->records);
        Carbon::setTestNow();
    }

    public function testFormat()
    {
        $accessLog = new AccessLog(function ($message) {
            return json_encode(Arrays::select($message, [
                'time_local', 'request_method', 'request_uri', 'status', 'body_bytes_sent', 'http_referer', 'http_user_agent', 'http_x_forwarded_for', 'request_time',
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        });
        $accessLog->setLogger($this->logger);
        $accessLog->process($this->createRequest(), $this->createHandler());
        // var_export($this->logger->records);
        $this->assertCount(1, $this->logger->records);
    }

    protected function createRequest(): \Laminas\Diactoros\ServerRequest
    {
        $request = ServerRequestFactory::fromGlobals([
            'REMOTE_ADDR' => '127.0.0.1',
            'REMOTE_PORT' => '52526',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '8099',
            'REQUEST_URI' => '/status.html',
            'QUERY_STRING' => 'foo=1',
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'localhost:8099',
            'HTTP_USER_AGENT' => 'curl/7.64.1',
        ], ['foo' => 1]);

        return $request;
    }

    protected function createHandler(): RequestHandlerInterface
    {
        $handler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $responseFactory = new ResponseFactory();

                return $responseFactory->createResponse();
            }
        };

        return $handler;
    }
}
