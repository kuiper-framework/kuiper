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

namespace kuiper\jsonrpc\annotation;

use kuiper\di\annotation\Service;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class JsonRpcService extends Service
{
    /**
     * @var string
     */
    public $service;

    /**
     * @var string
     */
    public $version;
}
