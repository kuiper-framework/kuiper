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

use Attribute;
use kuiper\di\attribute\ComponentTrait;
use kuiper\di\Component;

#[Attribute(Attribute::TARGET_CLASS)]
class JsonRpcService implements Component
{
    use ComponentTrait;

    public function __construct(
        private readonly ?string $service = null,
        private readonly ?string $version = null)
    {
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
