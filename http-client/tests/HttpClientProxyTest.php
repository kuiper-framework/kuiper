<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\annotations\AnnotationReader;
use kuiper\http\client\fixtures\GithubService;
use kuiper\http\client\fixtures\GitRepository;
use kuiper\serializer\Serializer;
use PHPUnit\Framework\TestCase;

class HttpClientProxyTest extends TestCase
{
    public function testGithubService()
    {
        $mock = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode([
                'code' => 0,
                'data' => [['name' => 'proj1']],
            ])),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $clientFactory = new HttpProxyClientFactory($client, AnnotationReader::getInstance(), new Serializer());
        $clientFactory->setRpcResponseFactory(new HttpJsonResponseFactory($clientFactory->getRpcResponseNormalizer(), 'data'));

        $proxy = $clientFactory->create(GithubService::class);
        $repos = $proxy->listRepos('john');
        // var_export($repos);
        $this->assertIsArray($repos);
        $this->assertInstanceOf(GitRepository::class, $repos[0]);
        $this->assertEquals('proj1', $repos[0]->getName());

        $request = $requests[0]['request'];
        $this->assertEquals('/users/john/list', (string) $request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('content-type'));
    }
}
