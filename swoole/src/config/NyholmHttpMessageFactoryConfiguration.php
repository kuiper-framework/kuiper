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

use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\swoole\attribute\BootstrapConfiguration;
use kuiper\swoole\http\NyholmSwooleRequestBridge;
use kuiper\swoole\http\SwooleRequestBridgeInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use function DI\autowire;
use function DI\get;

#[BootstrapConfiguration]
#[AllConditions(
    new ConditionalOnClass(Psr17Factory::class),
    new ConditionalOnProperty('application.server.http_factory', hasValue: 'nyholm', matchIfMissing: true)
)]
class NyholmHttpMessageFactoryConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            RequestFactoryInterface::class => get(Psr17Factory::class),
            ResponseFactoryInterface::class => get(Psr17Factory::class),
            StreamFactoryInterface::class => get(Psr17Factory::class),
            UriFactoryInterface::class => get(Psr17Factory::class),
            UploadedFileFactoryInterface::class => get(Psr17Factory::class),
            ServerRequestFactoryInterface::class => get(Psr17Factory::class),
            SwooleRequestBridgeInterface::class => autowire(NyholmSwooleRequestBridge::class),
        ];
    }
}
