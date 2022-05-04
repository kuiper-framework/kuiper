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
use kuiper\web\middleware\LoginOnly as LoginOnlyMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class LoginOnly extends AbstractMiddlewareFactory
{
    public function __construct(int $priority = 101)
    {
        parent::__construct($priority);
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerInterface $container): MiddlewareInterface
    {
        return new LoginOnlyMiddleware();
    }
}
