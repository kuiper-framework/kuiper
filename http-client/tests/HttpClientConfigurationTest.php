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

namespace kuiper\http\client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\http\client\fixtures\GithubService;
use kuiper\http\client\fixtures\GitRepository;
use kuiper\logger\Logger;
use kuiper\reflection\ReflectionConfiguration;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\serializer\SerializerConfiguration;
use kuiper\swoole\Application;
use kuiper\swoole\pool\PoolFactory;
use kuiper\swoole\pool\PoolFactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpClientConfigurationTest extends TestCase
{
    public function testName(): void
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

        $service = $this->createContainer([
            'application' => [
                'http_client' => [
                    GithubService::class => [
                        'handler' => $handlerStack,
                    ],
                ],
            ],
        ])->get(GithubService::class);
        $repos = $service->listRepos('john');
        // var_export($repos);
        $this->assertIsArray($repos);
        $this->assertInstanceOf(GitRepository::class, $repos[0]);
        $this->assertEquals('proj1', $repos[0]->getName());

        $request = $requests[0]['request'];
        $this->assertEquals('/users/john/list', (string) $request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('content-type'));
    }

    protected function createContainer(array $configArr): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new HttpClientConfiguration());
        $builder->addConfiguration(new ReflectionConfiguration());
        $builder->addConfiguration(new SerializerConfiguration());
        $app = Application::create(static function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $builder->setReflectionNamespaceFactory($reflectionNs);
        $builder->componentScan([__NAMESPACE__.'\\fixtures']);
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $builder->addDefinitions([
            LoggerInterface::class => Logger::nullLogger(),
            PoolFactoryInterface::class => new PoolFactory(false),
            PropertyResolverInterface::class => $config,
        ]);

        return $app->getContainer();
    }
}
