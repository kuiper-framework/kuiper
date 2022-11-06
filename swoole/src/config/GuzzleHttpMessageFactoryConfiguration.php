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

use kuiper\swoole\attribute\ServerStartConfiguration;
use function DI\autowire;
use function DI\get;
use GuzzleHttp\Psr7\HttpFactory;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\swoole\http\GuzzleSwooleRequestBridge;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

#[Configuration, ServerStartConfiguration]
#[AllConditions(
    new ConditionalOnClass(HttpFactory::class),
    new ConditionalOnProperty('application.server.http_factory', hasValue: 'guzzle', matchIfMissing: true)
)]
class GuzzleHttpMessageFactoryConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            RequestFactoryInterface::class => get(HttpFactory::class),
            ResponseFactoryInterface::class => get(HttpFactory::class),
            StreamFactoryInterface::class => get(HttpFactory::class),
            UriFactoryInterface::class => get(HttpFactory::class),
            UploadedFileFactoryInterface::class => get(HttpFactory::class),
            ServerRequestFactoryInterface::class => get(HttpFactory::class),
            SwooleRequestBridgeInterface::class => autowire(GuzzleSwooleRequestBridge::class),
        ];
    }
}
