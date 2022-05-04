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

namespace kuiper\rpc;

interface RpcMethodInterface
{
    /**
     * @return object|string
     */
    public function getTarget(): object|string;

    /**
     * @return string
     */
    public function getTargetClass(): string;

    /**
     * @return ServiceLocator
     */
    public function getServiceLocator(): ServiceLocator;

    /**
     * @return string
     */
    public function getMethodName(): string;

    /**
     * @return array
     */
    public function getArguments(): array;

    /**
     * @param array $args
     *
     * @return static
     */
    public function withArguments(array $args);

    /**
     * @return array
     */
    public function getResult(): array;

    /**
     * @param array $result
     *
     * @return static
     */
    public function withResult(array $result);

    /**
     * @return string
     */
    public function __toString(): string;
}
