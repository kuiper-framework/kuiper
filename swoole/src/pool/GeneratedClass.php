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

namespace kuiper\swoole\pool;

class GeneratedClass
{
    /**
     * GeneratedClass constructor.
     */
    public function __construct(
        private readonly string $className,
        private readonly string $code)
    {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function save(string $fileName): void
    {
        file_put_contents($fileName, $this->code);
    }

    public function eval(): void
    {
        if (class_exists($this->className, false)) {
            return;
        }
        eval($this->code);
    }
}
