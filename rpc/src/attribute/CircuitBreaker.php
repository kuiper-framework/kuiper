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

namespace kuiper\rpc\attribute;

use Attribute;
use kuiper\rpc\AbstractMiddlewareFactory;
use kuiper\rpc\MiddlewareInterface;
use Psr\Container\ContainerInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class CircuitBreaker extends AbstractMiddlewareFactory
{
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return $container->get(\kuiper\rpc\client\middleware\CircuitBreaker::class);
    }
}
