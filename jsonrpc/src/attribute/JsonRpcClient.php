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

namespace kuiper\jsonrpc\attribute;

use kuiper\rpc\attribute\RpcClient;

#[\Attribute(\Attribute::TARGET_CLASS)]
class JsonRpcClient extends RpcClient
{
    public function __construct(
        string  $service = '',
        string  $version = '',
        string  $namespace = '',
        string  $protocol = '',
        string  $endpoint = '',
        private readonly bool $outParams = false)
    {
        parent::__construct($service, $version, $namespace, $protocol, $endpoint);
    }

    /**
     * @return bool
     */
    public function isOutParams(): bool
    {
        return $this->outParams;
    }
}
