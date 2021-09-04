<?php

declare(strict_types=1);

namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareTest extends TestCase
{
    private $logs;

    protected function getConfig(): array
    {
        $this->logs = [];
        $logs = &$this->logs;

        return [
            'application' => [
                'web' => [
                    'middleware' => [
                        function (ServerRequestInterface $req, RequestHandlerInterface $handler) use (&$logs) {
                            $logs[] = 'middleware 1 run';

                            return $handler->handle($req);
                        },
                        function (ServerRequestInterface $req, RequestHandlerInterface $handler) use (&$logs) {
                            $logs[] = 'middleware 2 run';

                            return $handler->handle($req);
                        },
                    ],
                ],
            ],
        ];
    }

    public function testMiddlewares()
    {
        $response = $this->getContainer()->get(RequestHandlerInterface::class)
            ->handle($this->createRequest('GET /index'));
        // echo $response->getBody();
        $this->assertCount(2, $this->logs);
    }
}
