<?php

declare(strict_types=1);

namespace kuiper\tars\core;

interface MethodMetadataInterface
{
    public function getNamespace(): string;

    public function getClassName(): string;

    public function getMethodName(): string;

    public function getServantName(): string;

    /**
     * @return ParameterInterface[]
     */
    public function getParameters(): array;

    public function getReturnValue(): ParameterInterface;
}
