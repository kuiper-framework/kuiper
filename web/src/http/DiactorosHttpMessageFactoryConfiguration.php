<?php

declare(strict_types=1);

namespace kuiper\web\http;

use function DI\autowire;
use function DI\get;
use kuiper\di\annotation\AllConditions;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\swoole\http\DiactorosSwooleRequestBridge;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @Configuration()
 * @AllConditions(
 *     @ConditionalOnClass(ServerRequestFactory::class),
 *     @ConditionalOnProperty("application.web.http_factory", hasValue="diactoros", matchIfMissing=true)
 * )
 */
class DiactorosHttpMessageFactoryConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            RequestFactoryInterface::class => get(RequestFactory::class),
            ResponseFactoryInterface::class => get(ResponseFactory::class),
            StreamFactoryInterface::class => get(StreamFactory::class),
            UriFactoryInterface::class => get(UriFactory::class),
            UploadedFileFactoryInterface::class => get(UploadedFileFactory::class),
            ServerRequestFactoryInterface::class => get(ServerRequestFactory::class),
            SwooleRequestBridgeInterface::class => autowire(DiactorosSwooleRequestBridge::class),
        ];
    }
}
