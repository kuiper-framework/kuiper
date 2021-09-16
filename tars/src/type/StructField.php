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

namespace kuiper\tars\type;

class StructField
{
    /**
     * @var int
     */
    private $tag;
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var Type
     */
    private $type;

    /**
     * StructField constructor.
     */
    public function __construct(int $tag, string $name, Type $type, bool $required)
    {
        $this->tag = $tag;
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
    }

    public function getTag(): int
    {
        return $this->tag;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
