<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use kuiper\annotations\AnnotationReader;
use kuiper\http\client\fixtures\GithubService;
use kuiper\http\client\fixtures\GitRepository;
use kuiper\serializer\DocReader;
use kuiper\serializer\Serializer;
use kuiper\swoole\pool\PoolFactory;
use PHPUnit\Framework\TestCase;

class ProxyGeneratorImplTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testGithubService()
    {
        $httpClientFactory = new HttpClientFactory(new PoolFactory());
        $annotationReader = AnnotationReader::getInstance();
        $docReader = new DocReader();
        $methodMetadataFactory = new MethodMetadataFactory(
            $annotationReader,
            $docReader,
            new Serializer($annotationReader, $docReader)
        );
        $mock = new MockHandler([
            new Response(200, [
                'content-type' => 'application/json',
            ], json_encode([
                ['name' => 'a/b'],
            ])),
        ]);
        $handler = HandlerStack::create($mock);
        $httpClientProxyFactory = new HttpClientProxyFactory($httpClientFactory, $methodMetadataFactory, new ProxyGeneratorImpl(), [
            'handler' => $handler,
        ]);
        /** @var GithubService $service */
        $service = $httpClientProxyFactory->create(GithubService::class);
        $repositories = $service->listRepos('ywb');
        // var_export($repositories);
        $this->assertInstanceOf(GitRepository::class, $repositories[0]);
    }
}
