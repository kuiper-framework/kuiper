<?php

declare(strict_types=1);

namespace kuiper\tars\core;

interface MethodMetadataFactoryInterface
{
    /**
     * @param object|string $servant
     */
    public function create($servant, string $method): MethodMetadata;
}
