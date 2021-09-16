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

namespace kuiper\web;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\AwareInjection;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use kuiper\helper\PropertyResolverInterface;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\swoole\Application;
use kuiper\swoole\config\DiactorosHttpMessageFactoryConfiguration;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createContainer(array $configArr): ContainerInterface
    {
        $_SERVER['APP_PATH'] = dirname(__DIR__, 2);
        $builder = new ContainerBuilder();
        $builder->addConfiguration(new WebConfiguration());
        $app = Application::create(function () use ($builder) {
            return $builder->build();
        });
        $config = Application::getInstance()->getConfig();
        $config->merge($configArr);
        $builder->addDefinitions(new PropertiesDefinitionSource($config));
        $builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
        /** @var ReflectionNamespaceFactory $reflectionNs */
        $reflectionNs = ReflectionNamespaceFactory::getInstance();
        $reflectionNs->register(__NAMESPACE__.'\\fixtures', __DIR__.'/fixtures');
        $builder->setReflectionNamespaceFactory($reflectionNs);
        $builder->componentScan([__NAMESPACE__.'\\fixtures']);
        $builder->addAwareInjection(AwareInjection::create(LoggerAwareInterface::class));
        $builder->addDefinitions([
            LoggerInterface::class => new NullLogger(),
            PropertyResolverInterface::class => $config,
            AnnotationReaderInterface::class => AnnotationReader::getInstance(),
        ]);

        return $app->getContainer();
    }

    protected function getConfig(): array
    {
        return [];
    }

    protected function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new \BadMethodCallException('call createContainer first');
        }

        return $this->container;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer($this->getConfig());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    public function createRequest($req): ServerRequestInterface
    {
        [$method, $url] = explode(' ', $req, 2);
        $result = parse_url($url);
        if (isset($result['host'])) {
            $host = $result['host'].(isset($result['port']) ? ':'.$result['port'] : '');
        } else {
            $host = 'localhost';
        }

        return (new ServerRequestFactory())
            ->createServerRequest($method, sprintf('%s://%s%s', $result['scheme'] ?? 'http', $host, $result['path']));
    }
}
