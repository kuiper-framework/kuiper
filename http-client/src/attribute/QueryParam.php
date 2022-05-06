<?php

namespace kuiper\http\client\attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParam
{
    public function __construct(private readonly ?string $name = null)
    {
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}