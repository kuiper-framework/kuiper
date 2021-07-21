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
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\serializer\Serializer;
use PHPUnit\Framework\TestCase;

class HttpClientProxyTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testGithubService()
    {
        $mock = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode([['name' => 'proj1']])),
        ]);
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);
        $httpTransporter = new HttpTransporter($client);
        $annotationReader = AnnotationReader::getInstance();
        $reflectionDocBlockFactory = new ReflectionDocBlockFactory();
        $normalizer = new Serializer($annotationReader, $reflectionDocBlockFactory);
        $responseFactory = new JsonResponseFactory(new RpcResponseNormalizer($normalizer, $reflectionDocBlockFactory));

        $proxyGenerator = new ProxyGenerator($reflectionDocBlockFactory);
        $generatedClass = $proxyGenerator->generate(GithubService::class);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();
        /** @var GithubService $proxy */
        $proxy = new $class(new RpcClient($httpTransporter, new HttpRequestFactory($annotationReader, $normalizer), $responseFactory));
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
