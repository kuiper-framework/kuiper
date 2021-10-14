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

namespace kuiper\rpc\annotation;

use kuiper\di\annotation\ComponentInterface;
use kuiper\di\annotation\ComponentTrait;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class RpcClient implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var string
     */
    public $protocol;

    /**
     * @var string
     */
    public $endpoint;
}
