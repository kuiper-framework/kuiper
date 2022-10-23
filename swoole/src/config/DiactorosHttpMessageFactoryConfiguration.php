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

namespace kuiper\swoole\config;

use function DI\autowire;
use function DI\get;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
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

#[Configuration]
#[AllConditions(
    new ConditionalOnClass(ServerRequestFactory::class),
    new ConditionalOnProperty('application.server.http_factory', hasValue: 'diactoros', matchIfMissing: true)
)]
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
