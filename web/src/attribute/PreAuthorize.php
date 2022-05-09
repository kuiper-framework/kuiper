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

namespace kuiper\web\attribute;

use kuiper\web\middleware\AbstractMiddlewareFactory;
use kuiper\web\middleware\PreAuthorize as PreAuthorizeMiddleware;
use kuiper\web\security\AclInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class PreAuthorize extends AbstractMiddlewareFactory
{
    /**
     * @param string[] $requiredAuthorities
     * @param string[] $anyAuthorities
     */
    public function __construct(
        private readonly array $requiredAuthorities,
        private readonly array $anyAuthorities = [])
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return new PreAuthorizeMiddleware($container->get(AclInterface::class), $this->requiredAuthorities, $this->anyAuthorities);
    }
}
