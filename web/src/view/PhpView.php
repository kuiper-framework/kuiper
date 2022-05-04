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

namespace kuiper\web\view;

class PhpView implements ViewInterface
{
    public function __construct(
        private readonly string $baseDir,
        private readonly string $extension = '.php')
    {
    }

    public function render(string $name, array $context = []): string
    {
        extract($context, EXTR_SKIP);
        ob_start();
        include $this->baseDir.'/'.$name.$this->extension;

        return ob_get_clean();
    }
}
