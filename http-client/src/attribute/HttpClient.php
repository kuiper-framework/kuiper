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

namespace kuiper\http\client\attribute;

use Attribute;
use kuiper\rpc\attribute\RpcClient;

#[Attribute(Attribute::TARGET_CLASS)]
class HttpClient extends RpcClient
{
    public function __construct(
        string $service = '',
        string $version = '',
        string $namespace = '',
        string $protocol = '',
        string $endpoint = '',
        private readonly string $url = '',
        private readonly string $path = '',
        private readonly string $responseParser = '')
    {
        parent::__construct($service, $version, $namespace, $protocol, $endpoint);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getResponseParser(): string
    {
        return $this->responseParser;
    }
}
