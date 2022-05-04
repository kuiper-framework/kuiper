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

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequestMapping
{
    /**
     * @param string|string[] $mapping The path mapping URIs. type is string|string[].
     * @param string $name Assign a name to this mapping.
     * @param string[] $method The HTTP request methods to map to.
     */
    public function __construct(
        private readonly string|array $mapping,
        private readonly string $name = '',
        private readonly array $method = [])
    {
    }

    /**
     * @return string[]
     */
    public function getMapping(): array
    {
        return (array) $this->mapping;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getMethod(): array
    {
        return $this->method;
    }
}
