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

namespace kuiper\tars\attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class TarsParameter
{
    public function __construct(
        private readonly string $type,
        private readonly ?int $order = null,
        private readonly bool $required = false,
        private readonly bool $routeKey = false)
    {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return bool
     */
    public function isOut(): bool
    {
        return $this->out;
    }

    /**
     * @return bool
     */
    public function isRouteKey(): bool
    {
        return $this->routeKey;
    }
}
