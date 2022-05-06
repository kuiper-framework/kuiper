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

use kuiper\di\attribute\Service;

#[\Attribute(\Attribute::TARGET_CLASS)]
class JsonRpcService extends Service
{
    public function __construct(
        ?string $value = null,
        private readonly ?string $service = null,
        private readonly ?string $version = null)
    {
        parent::__construct($value);
    }

    /**
     * @return string|null
     */
    public function getService(): ?string
    {
        return $this->service;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

}
